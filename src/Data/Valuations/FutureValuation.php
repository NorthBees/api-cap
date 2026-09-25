<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Valuations;

use NorthBees\CapApi\Xml\DataSetReader;

/**
 * A projected residual value from the FutureValues service.
 */
final readonly class FutureValuation
{
    public function __construct(
        public ?int $value,
        public int $monthsToValuation,
        public int $mileage,
    ) {}

    public static function fromDataSet(DataSetReader $reader, int $monthsToValuation, int $mileage): self
    {
        return new self($reader->first('Valuation')?->int('valuation'), $monthsToValuation, $mileage);
    }
}
