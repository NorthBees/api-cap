<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Illuminate\Support\Collection;
use NorthBees\CapApi\Data\Taxonomy\CapDescription;
use NorthBees\CapApi\Data\Taxonomy\Derivative;
use NorthBees\CapApi\Data\Taxonomy\Manufacturer;
use NorthBees\CapApi\Data\Taxonomy\Model;
use NorthBees\CapApi\Data\Taxonomy\Range;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Xml\Row;

/**
 * Taxonomy (manufacturer → range → model → derivative) and CAP ID / CAP code conversion.
 */
final class VehiclesResource extends Resource
{
    protected function service(): CapService
    {
        return CapService::Vehicles;
    }

    /**
     * WSDL: subscriberId, password, database, justCurrentManufacturers:boolean, bodyStyleFilter?:string
     *
     * @return Collection<int, Manufacturer>
     */
    public function manufacturers(bool $justCurrent = true, bool $includeOnRunout = false, ?string $bodyStyleFilter = null, ?CapDatabase $database = null): Collection
    {
        return $this->rows($includeOnRunout ? 'GetCapMan_IncludeOnRunout' : 'GetCapMan', [
            'database' => $this->database($database),
            'justCurrentManufacturers' => $justCurrent,
            'bodyStyleFilter' => $bodyStyleFilter,
        ])->map(Manufacturer::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, manCode:int, justCurrentRanges:boolean, bodyStyleFilter?:string
     *
     * @return Collection<int, Range>
     */
    public function ranges(int $manufacturerCode, bool $justCurrent = true, bool $includeOnRunout = false, ?string $bodyStyleFilter = null, ?CapDatabase $database = null): Collection
    {
        return $this->rows($includeOnRunout ? 'GetCapRange_IncludeOnRunout' : 'GetCapRange', [
            'database' => $this->database($database),
            'manCode' => $manufacturerCode,
            'justCurrentRanges' => $justCurrent,
            'bodyStyleFilter' => $bodyStyleFilter,
        ])->map(Range::fromRow(...));
    }

    /**
     * Models within a range (or a whole manufacturer when $isManufacturerCode is true).
     *
     * WSDL: subscriberId, password, database, manRanCode:int, manRanCodeIsMan:boolean, justCurrentModels:boolean, bodyStyleFilter?:string
     *
     * @return Collection<int, Model>
     */
    public function models(int $rangeCode, bool $justCurrent = true, bool $includeOnRunout = false, bool $isManufacturerCode = false, ?string $bodyStyleFilter = null, ?CapDatabase $database = null): Collection
    {
        return $this->rows($includeOnRunout ? 'GetCapMod_IncludeOnRunout' : 'GetCapMod', [
            'database' => $this->database($database),
            'manRanCode' => $rangeCode,
            'manRanCodeIsMan' => $isManufacturerCode,
            'justCurrentModels' => $justCurrent,
            'bodyStyleFilter' => $bodyStyleFilter,
        ])->map(Model::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, modCode:int, justCurrentDerivatives:boolean
     *
     * @return Collection<int, Derivative>
     */
    public function derivatives(int $modelCode, bool $justCurrent = true, bool $includeOnRunout = false, ?CapDatabase $database = null): Collection
    {
        return $this->rows($includeOnRunout ? 'GetCapDer_IncludeOnRunout' : 'GetCapDer', [
            'database' => $this->database($database),
            'modCode' => $modelCode,
            'justCurrentDerivatives' => $justCurrent,
        ])->map(Derivative::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, ranCode:int, justCurrentDerivatives:boolean
     *
     * @return Collection<int, Derivative>
     */
    public function derivativesForRange(int $rangeCode, bool $justCurrent = true, bool $includeOnRunout = false, ?CapDatabase $database = null): Collection
    {
        return $this->rows($includeOnRunout ? 'GetCapDerFromRange_IncludeOnRunout' : 'GetCapDerFromRange', [
            'database' => $this->database($database),
            'ranCode' => $rangeCode,
            'justCurrentDerivatives' => $justCurrent,
        ])->map(Derivative::fromRow(...));
    }

    /**
     * WSDL: subscriberId, password, database, capid:int
     */
    public function description(int $capId, ?CapDatabase $database = null): ?CapDescription
    {
        $row = $this->rows('GetCapDescriptionFromId', ['database' => $this->database($database), 'capid' => $capId])->first();

        return $row === null ? null : CapDescription::fromRow($capId, $row);
    }

    /**
     * WSDL: subscriberId, password, database, capid:int
     */
    public function capCodeFor(int $capId, ?CapDatabase $database = null): ?string
    {
        return $this->rows('GetCapcodeFromCapid', ['database' => $this->database($database), 'capid' => $capId])
            ->first()
            ?->string('CDer_CAPcode');
    }

    /**
     * WSDL: subscriberId, password, database, caPcode:string
     */
    public function capIdFor(string $capCode, ?CapDatabase $database = null): ?int
    {
        return $this->rows('GetCapidFromCapcode', ['database' => $this->database($database), 'caPcode' => $capCode])
            ->first()
            ?->int('CDer_ID') ?: null;
    }

    /**
     * @param  array<string, bool|int|string|null>  $params
     * @return Collection<int, Row>
     */
    private function rows(string $operation, array $params): Collection
    {
        return collect($this->call($operation, $params)->dataSet()->rows());
    }
}
