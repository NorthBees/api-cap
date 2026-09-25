<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonInterface;
use NorthBees\CapApi\Data\Valuations\LiveValuation;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;

/**
 * CAP Live daily used values.
 */
final class UsedValuesLiveResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::UsedValuesLive;
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, valuationDate:dateTime, regDate:dateTime, mileage:int
     */
    public function valuation(int $capId, CarbonInterface $registeredAt, int $mileage, ?CarbonInterface $valuationDate = null, ?CapDatabase $database = null): LiveValuation
    {
        return LiveValuation::fromResult($this->call('GetUsedLive_IdRegDateMileage', [
            'database' => $this->database($database),
            'capid' => $capId,
            'valuationDate' => $this->date($valuationDate),
            'regDate' => $this->date($registeredAt),
            'mileage' => $mileage,
        ]));
    }
}
