<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonInterface;
use NorthBees\CapApi\Data\Valuations\FutureValuation;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;

/**
 * Projected residual values.
 */
final class FutureValuesResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::FutureValues;
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, capCode?:string, registrationDate:dateTime,
     *       datasetDate:dateTime, justCurrent:boolean, monthsToValuation:int, mileage:int
     *
     * @param  int  $mileage  the expected mileage at the future valuation point
     */
    public function valuation(int $capId, CarbonInterface $registeredAt, int $monthsToValuation, int $mileage, ?string $capCode = null, ?CarbonInterface $datasetDate = null, bool $justCurrent = true, ?CapDatabase $database = null): FutureValuation
    {
        return FutureValuation::fromDataSet($this->call('GetFutureValuation', [
            'database' => $this->database($database),
            'capid' => $capId,
            'capCode' => $capCode,
            'registrationDate' => $this->date($registeredAt),
            'datasetDate' => $this->date($datasetDate),
            'justCurrent' => $justCurrent,
            'monthsToValuation' => $monthsToValuation,
            'mileage' => $mileage,
        ])->dataSet(), $monthsToValuation, $mileage);
    }
}
