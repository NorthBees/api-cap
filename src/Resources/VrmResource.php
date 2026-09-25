<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonInterface;
use NorthBees\CapApi\Data\Valuations\VrmValuation;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapNoMatchException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;
use NorthBees\CapApi\Soap\SoapResult;

/**
 * One-call identification + valuation (+ optional standard equipment).
 * Chargeable per call, so never retried beyond connection failures.
 */
final class VrmResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::Vrm;
    }

    /**
     * WSDL: SubscriberID, Password, VRM:string, Mileage:int, StandardEquipmentRequired:boolean
     */
    public function valuationByVrm(string $vrm, int $mileage, bool $withStandardEquipment = false): VrmValuation
    {
        return $this->valuation('VRMValuation', 'VRMLookup', [
            'VRM' => DvlaResource::normalise($vrm),
            'Mileage' => $mileage,
            'StandardEquipmentRequired' => $withStandardEquipment,
        ]);
    }

    /**
     * WSDL: SubscriberID, Password, Database, CAPID:int, RegisteredDate:dateTime, Mileage:int, StandardEquipmentRequired:boolean
     */
    public function valuationByCapId(int $capId, CarbonInterface $registeredAt, int $mileage, bool $withStandardEquipment = false, ?CapDatabase $database = null): VrmValuation
    {
        return $this->valuation('CAPIDValuation', 'CAPIDLookup', [
            'Database' => $this->database($database),
            'CAPID' => $capId,
            'RegisteredDate' => $this->date($registeredAt),
            'Mileage' => $mileage,
            'StandardEquipmentRequired' => $withStandardEquipment,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function valuation(string $operation, string $lookupElement, array $params): VrmValuation
    {
        $result = $this->call($operation, $params, retry: false, assertSuccess: false);
        $valuation = VrmValuation::fromResult($result, $lookupElement);

        if (! $valuation->lookup->found) {
            throw new CapNoMatchException('CAP could not identify the vehicle.', $this->service(), $operation);
        }

        $this->assertSucceeded($result, $operation);

        return $valuation;
    }

    private function assertSucceeded(SoapResult $result, string $operation): void
    {
        $row = $result->row();

        if ($row->bool('Success') === false && $row->bool('MileageOutOfBounds') !== true) {
            throw new CapRequestFailedException("CAP {$operation} failed: ".($row->string('FailReason') ?? 'Unknown error'), $this->service(), $operation);
        }
    }
}
