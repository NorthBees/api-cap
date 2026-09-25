<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Dvla;

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Xml\Row;

/**
 * The DATA/DVLA block of a DVLA lookup: registration-document data.
 */
final readonly class DvlaRecord
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public ?string $vrm,
        public ?string $vin,
        public ?string $manufacturer,
        public ?string $model,
        public ?string $fuelType,
        public ?string $bodyType,
        public ?int $seats,
        public ?string $colour,
        public ?CarbonImmutable $registrationDate,
        public ?CarbonImmutable $firstRegistrationDate,
        public ?CarbonImmutable $manufacturedDate,
        public ?int $co2,
        public ?int $engineCapacity,
        public ?string $engineNumber,
        public ?int $maxNetPowerKw,
        public ?int $previousKeepers,
        public bool $isImported,
        public bool $isExported,
        public bool $isScrapped,
        public array $attributes,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self(
            vrm: $row->string('VRM'),
            vin: $row->string('VIN'),
            manufacturer: $row->string('MANUFACTURER'),
            model: $row->string('MODEL'),
            fuelType: $row->string('FUELTYPE'),
            bodyType: $row->string('BODYTYPE'),
            seats: $row->int('SEATING'),
            colour: $row->string('COLOUR'),
            registrationDate: $row->date('REGISTRATIONDATE'),
            firstRegistrationDate: $row->date('FIRSTREG_DATE'),
            manufacturedDate: $row->date('MANUFACTUREDDATE'),
            co2: $row->int('CO2'),
            engineCapacity: $row->int('ENGINECAPACITY'),
            engineNumber: $row->string('ENGINENUMBER'),
            maxNetPowerKw: $row->int('MAXNETPOWER'),
            previousKeepers: $row->int('PREVIOUSKEEPERS'),
            isImported: $row->bool('ISIMPORTED') ?? false,
            isExported: $row->bool('ISEXPORTED') ?? false,
            isScrapped: $row->bool('ISSCRAPPED') ?? false,
            attributes: $row->toArray(),
        );
    }
}
