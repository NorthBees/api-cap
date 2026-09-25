<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Taxonomy;

use NorthBees\CapApi\Xml\Row;

/**
 * The full text description of a CAP ID.
 */
final readonly class CapDescription
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public int $capId,
        public ?string $manufacturer,
        public ?string $manufacturerCode,
        public ?string $model,
        public ?string $modelShort,
        public ?string $modelCode,
        public ?int $modelId,
        public ?string $derivative,
        public ?string $derivativeShort,
        public array $attributes,
    ) {}

    public static function fromRow(int $capId, Row $row): self
    {
        return new self(
            capId: $capId,
            manufacturer: $row->string('CVehicle_ManText'),
            manufacturerCode: $row->string('CVehicle_ManTextCode'),
            model: $row->string('CVehicle_ModText'),
            modelShort: $row->string('CVehicle_ShortModText'),
            modelCode: $row->string('CVehicle_ModTextCode'),
            modelId: $row->int('CVehicle_ShortModID'),
            derivative: $row->string('CVehicle_DerText'),
            derivativeShort: $row->string('CVehicle_ShortDerText'),
            attributes: $row->toArray(),
        );
    }
}
