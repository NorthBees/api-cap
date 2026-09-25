<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\DataSetReader;

final readonly class P11dData
{
    public function __construct(
        public int $capId,
        public ?int $co2,
        public ?string $euroEmissions,
        public ?int $engineCapacity,
        public ?string $fuelType,
    ) {}

    public static function fromDataSet(int $capId, DataSetReader $reader): self
    {
        return new self(
            capId: $capId,
            co2: $reader->first('CO2_Table')?->int('CO2'),
            euroEmissions: $reader->first('Euro_Emissions_Table')?->string('Euro_Emissions'),
            engineCapacity: $reader->first('CC_Table')?->int('CC'),
            fuelType: $reader->first('FuelType_Table')?->string('FuelType'),
        );
    }
}
