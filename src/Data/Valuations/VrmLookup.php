<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Valuations;

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Support\CapCode;
use NorthBees\CapApi\Xml\Row;

/**
 * The VRMLookup / CAPIDLookup block of a VRM service valuation.
 */
final readonly class VrmLookup
{
    public function __construct(
        public bool $found,
        public ?CapDatabase $database,
        public ?int $capId,
        public ?CapCode $capCode,
        public ?string $manufacturer,
        public ?string $range,
        public ?string $model,
        public ?string $derivative,
        public ?int $modelIntroducedYear,
        public ?int $modelDiscontinuedYear,
        public ?CarbonImmutable $derivativeIntroduced,
        public ?CarbonImmutable $derivativeDiscontinued,
        public ?CarbonImmutable $registeredDate,
        public ?string $vin,
        public ?string $engineNumber,
        public ?string $colour,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self(
            found: ($row->bool('VehicleFound') ?? $row->bool('IDFound')) === true,
            database: CapDatabase::tryFrom((string) $row->string('Database')),
            capId: $row->int('CAPID') ?: null,
            capCode: CapCode::tryFrom($row->string('CAPcode')),
            manufacturer: $row->string('CAPMan'),
            range: $row->string('CAPRange'),
            model: $row->string('CAPMod'),
            derivative: $row->string('CAPDer'),
            modelIntroducedYear: $row->int('ModIntroduced') ?: null,
            modelDiscontinuedYear: $row->int('ModDiscontinued') ?: null,
            derivativeIntroduced: $row->date('DerIntroduced'),
            derivativeDiscontinued: $row->date('DerDiscontinued'),
            registeredDate: $row->date('RegisteredDate'),
            vin: $row->string('VinNumber'),
            engineNumber: $row->string('EngineNumber'),
            colour: $row->string('Colour'),
        );
    }
}
