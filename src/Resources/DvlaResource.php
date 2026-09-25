<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use NorthBees\CapApi\Data\Dvla\DvlaLookupResult;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapLookupLimitExceededException;
use NorthBees\CapApi\Exceptions\CapNoMatchException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;

/**
 * DVLA / SMMT / CAP identification by VRM or VIN.
 *
 * Each call counts against the subscriber's monthly lookup allowance, so these
 * calls are never retried beyond connection failures.
 */
final class DvlaResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::Dvla;
    }

    /**
     * WSDL: SubscriberID:int, Password:string, vrm:string
     *
     * @throws CapNoMatchException|CapLookupLimitExceededException|CapRequestFailedException
     */
    public function lookupVrm(string $vrm): DvlaLookupResult
    {
        return $this->lookup('DVLALookupVRM', ['vrm' => self::normalise($vrm)]);
    }

    /**
     * WSDL: SubscriberID:int, Password:string, vin:string
     *
     * @throws CapNoMatchException|CapLookupLimitExceededException|CapRequestFailedException
     */
    public function lookupVin(string $vin): DvlaLookupResult
    {
        return $this->lookup('DVLALookupVIN', ['vin' => self::normalise($vin)]);
    }

    public static function normalise(string $identifier): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $identifier));
    }

    /**
     * @param  array<string, string>  $params
     */
    private function lookup(string $operation, array $params): DvlaLookupResult
    {
        $result = DvlaLookupResult::fromXml($this->call($operation, $params, retry: false)->embeddedXml());

        if ($result->monthlyLookupLimitExceeded) {
            throw new CapLookupLimitExceededException('CAP monthly DVLA lookup limit exceeded.', $this->service(), $operation);
        }

        if (! $result->success && ! $result->isMatched()) {
            throw new CapRequestFailedException('CAP '.$operation.' failed: '.($result->errorMessage ?? 'Unknown error'), $this->service(), $operation);
        }

        if (! $result->isMatched()) {
            throw new CapNoMatchException('CAP could not identify the vehicle.', $this->service(), $operation);
        }

        return $result;
    }
}
