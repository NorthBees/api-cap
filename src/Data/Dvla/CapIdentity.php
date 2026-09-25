<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Dvla;

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Support\CapCode;
use NorthBees\CapApi\Xml\Row;

/**
 * The DATA/CAP block of a DVLA lookup: the matched CAP derivative.
 */
final readonly class CapIdentity
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public int $capId,
        public ?CapCode $capCode,
        public CapDatabase $database,
        public ?string $vehicleType,
        public ?string $enhancedCapCode,
        public ?int $manufacturerCode,
        public ?string $manufacturer,
        public ?int $rangeCode,
        public ?string $range,
        public ?int $modelCode,
        public ?string $model,
        public ?string $derivative,
        public ?string $modelDateShort,
        public ?string $modelDateLong,
        public ?CarbonImmutable $introduced,
        public ?CarbonImmutable $discontinued,
        public ?int $doors,
        public ?string $drivetrain,
        public ?string $fuelDelivery,
        public ?string $transmission,
        public ?string $fuelType,
        public ?string $plate,
        public ?int $plateYear,
        public ?int $plateMonth,
        public array $attributes,
    ) {}

    public static function fromRow(Row $row): self
    {
        $capCode = CapCode::tryFrom($row->string('CAPCODE'));

        return new self(
            capId: (int) $row->int('CAPID'),
            capCode: $capCode,
            database: CapDatabase::fromVehicleType($row->string('VEHICLETYPE')),
            vehicleType: $row->string('VEHICLETYPE'),
            enhancedCapCode: $row->string('ENHANCEDCAPCODE'),
            manufacturerCode: $row->int('MANUFACTURER_CODE'),
            manufacturer: $row->string('MANUFACTURER'),
            rangeCode: $row->int('RANGE_CODE'),
            range: $row->string('RANGE'),
            modelCode: $row->int('MODEL_CODE'),
            model: $row->string('MODEL'),
            derivative: $row->string('DERIVATIVE'),
            modelDateShort: $row->string('MODELDATEDESC_SHORT'),
            modelDateLong: $row->string('MODELDATEDESC_LONG'),
            introduced: $row->date('INTRODUCED'),
            discontinued: $row->date('DISCONTINUED'),
            doors: $row->int('DOORS'),
            drivetrain: $row->string('DRIVETRAIN'),
            fuelDelivery: $row->string('FUELDELIVERY'),
            transmission: $row->string('TRANSMISSION') ?? $capCode?->transmission(),
            fuelType: $row->string('FUELTYPE'),
            plate: $row->string('PLATE'),
            plateYear: $row->int('PLATE_YEAR'),
            plateMonth: $row->int('PLATE_MONTH'),
            attributes: $row->toArray(),
        );
    }
}
