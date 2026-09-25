<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonInterface;
use NorthBees\CapApi\Data\Valuations\UsedValuation;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapNoMatchException;

/**
 * CAP Black Book monthly used values.
 */
final class UsedValuesResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::UsedValues;
    }

    /**
     * Valuation by CAP ID (or CAP code), registration date and mileage.
     *
     * WSDL: Subscriber_ID, Password, Database, CAPID:int, CAPCode?:string, RegistrationDate:dateTime,
     *       DatasetDate:dateTime, JustCurrent:boolean, Mileage:int
     *
     * @throws CapNoMatchException when CAP returns no valuation rows
     */
    public function valuation(int $capId, CarbonInterface $registeredAt, int $mileage, ?string $capCode = null, ?CarbonInterface $datasetDate = null, bool $justCurrent = true, ?CapDatabase $database = null): UsedValuation
    {
        $row = $this->call('GetUsedValuation', [
            'Database' => $this->database($database),
            'CAPID' => $capCode === null ? $capId : 0,
            'CAPCode' => $capCode,
            'RegistrationDate' => $this->date($registeredAt),
            'DatasetDate' => $this->date($datasetDate),
            'JustCurrent' => $justCurrent,
            'Mileage' => $mileage,
        ])->dataSet()->first();

        if ($row === null) {
            throw new CapNoMatchException('CAP returned no used valuation.', $this->service(), 'GetUsedValuation');
        }

        return UsedValuation::fromRow($row, $mileage);
    }

    /**
     * Valuation by CAP ID, plate year/month and mileage.
     *
     * WSDL: Subscriber_ID, Password, Database, CAPID:int, UsedValuesDate:dateTime, JustCurrent:boolean,
     *       Year:int, Month:int, Mileage:int
     */
    public function valuationForYearMonth(int $capId, int $year, int $month, int $mileage, ?CarbonInterface $usedValuesDate = null, bool $justCurrent = true, ?CapDatabase $database = null): UsedValuation
    {
        $result = $this->call('GetUsedValuesForIDYearMonthMileage', [
            'Database' => $this->database($database),
            'CAPID' => $capId,
            'UsedValuesDate' => $this->date($usedValuesDate)->startOfMonth(),
            'JustCurrent' => $justCurrent,
            'Year' => $year,
            'Month' => $month,
            'Mileage' => $mileage,
        ]);

        return UsedValuation::fromRow($result->row(), $mileage);
    }
}
