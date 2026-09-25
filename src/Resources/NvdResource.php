<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use NorthBees\CapApi\Data\Nvd\BulkTechnicalData;
use NorthBees\CapApi\Data\Nvd\OptionBundle;
use NorthBees\CapApi\Data\Nvd\P11dData;
use NorthBees\CapApi\Data\Nvd\StandardEquipmentItem;
use NorthBees\CapApi\Data\Nvd\TechnicalData;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;

/**
 * New Vehicle Data: standard equipment, technical data, options and P11D.
 *
 * Technical data is only returned for dates on or after the model's introduction,
 * so pass max(first registration, CAP introduced date) for older vehicles.
 */
final class NvdResource extends Resource
{
    /** Every item GetBulkTechnicalData supports. */
    public const string ALL_TECH_DATA_ITEMS = '0TO62, CC, CO, CO2, MPG_COMBINED, MPG_URBAN, MPG_EXTRAURBAN, '
        .'ENGINEPOWER_BHP, ENGINEPOWER_KW, ENGINEPOWER_RPM, FUELDELIVERY, FUELTANKCAPACITY, GROSSVEHICLEWEIGHT, HC, '
        .'HC+NOX, HEIGHT, INSURANCEGROUP1, INSURANCEGROUP2, LENGTH, '
        .'LUGGAGECAPACITYSEATSDOWN, LUGGAGECAPACITYSEATSUP, NCAPADULTOCCUPANT, NCAPCHILDOCCUPANT, NCAPOVERALLRATING, '
        .'NCAPPEDESTRIAN, NCAPSAFETYASSIST, SEATS, NOX, STANDARDEMISSIONS, STANDARDMANWARRANTY_MILEAGE, '
        .'STANDARDMANWARRANTY_YEARS, TOPSPEED, WIDTH';

    protected function service(): CapService
    {
        return CapService::Nvd;
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, seDate:dateTime, justCurrent:boolean
     *
     * @return Collection<int, StandardEquipmentItem>
     */
    public function standardEquipment(int $capId, ?CarbonInterface $at = null, bool $justCurrent = false, ?CapDatabase $database = null): Collection
    {
        $rows = $this->call('GetStandardEquipment', [
            'database' => $this->database($database),
            'capid' => $capId,
            'seDate' => $this->date($at),
            'justCurrent' => $justCurrent,
        ])->dataSet()->rows();

        return collect($rows)->map(StandardEquipmentItem::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, techDate:dateTime, justCurrent:boolean
     */
    public function technicalData(int $capId, ?CarbonInterface $at = null, bool $justCurrent = false, ?CapDatabase $database = null): TechnicalData
    {
        return TechnicalData::fromDataSet($capId, $this->call('GetTechnicalData', [
            'database' => $this->database($database),
            'capid' => $capId,
            'techDate' => $this->date($at),
            'justCurrent' => $justCurrent,
        ])->dataSet());
    }

    /**
     * WSDL: subscriberId, password, database, capidList:string, specDateList:string, techDataList:string,
     *       returnVehicleDescription:boolean, returnCaPcodeTechnicalItems:boolean, returnCostNew:boolean
     *
     * @param  list<int>  $capIds
     * @return Collection<int, BulkTechnicalData>
     */
    public function bulkTechnicalData(
        array $capIds,
        ?CarbonInterface $at = null,
        string $techDataList = self::ALL_TECH_DATA_ITEMS,
        bool $returnVehicleDescription = true,
        bool $returnCapCodeTechnicalItems = true,
        bool $returnCostNew = true,
        ?CapDatabase $database = null,
    ): Collection {
        $specDate = $this->date($at)->format('Y/m/d');

        $rows = $this->call('GetBulkTechnicalData', [
            'database' => $this->database($database),
            'capidList' => implode(',', $capIds),
            'specDateList' => implode(',', array_fill(0, count($capIds), $specDate)),
            'techDataList' => $techDataList,
            'returnVehicleDescription' => $returnVehicleDescription,
            'returnCaPcodeTechnicalItems' => $returnCapCodeTechnicalItems,
            'returnCostNew' => $returnCostNew,
        ])->dataSet()->rows();

        return collect($rows)->map(BulkTechnicalData::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, optionDate:dateTime, justCurrent:boolean,
     *       descriptionRs, optionsRs, relationshipsRs, packRs, technicalRs (all boolean)
     */
    public function optionsBundle(
        int $capId,
        ?CarbonInterface $at = null,
        bool $justCurrent = false,
        bool $description = true,
        bool $options = true,
        bool $relationships = true,
        bool $packs = true,
        bool $technical = false,
        ?CapDatabase $database = null,
    ): OptionBundle {
        return new OptionBundle($capId, $this->call('GetCapOptionsBundle', [
            'database' => $this->database($database),
            'capid' => $capId,
            'optionDate' => $this->date($at),
            'justCurrent' => $justCurrent,
            'descriptionRs' => $description,
            'optionsRs' => $options,
            'relationshipsRs' => $relationships,
            'packRs' => $packs,
            'technicalRs' => $technical,
        ])->dataSet());
    }

    /**
     * WSDL: subscriberId, password, database, capid:int, year1:int, year2:int, year3:int
     */
    public function p11d(int $capId, int $year1, int $year2, int $year3, ?CapDatabase $database = null): P11dData
    {
        return P11dData::fromDataSet($capId, $this->call('GetP11DData', [
            'database' => $this->database($database),
            'capid' => $capId,
            'year1' => $year1,
            'year2' => $year2,
            'year3' => $year3,
        ])->dataSet());
    }
}
