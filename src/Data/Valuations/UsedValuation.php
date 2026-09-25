<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Valuations;

use NorthBees\CapApi\Xml\Row;

/**
 * CAP's four used-value positions, in whole pounds.
 */
final readonly class UsedValuation
{
    public function __construct(
        public ?int $retail,
        public ?int $clean,
        public ?int $average,
        public ?int $below,
        public ?int $mileage = null,
    ) {}

    public static function fromRow(Row $row, ?int $mileage = null): self
    {
        return new self(
            retail: $row->int('Retail'),
            clean: $row->int('Clean'),
            average: $row->int('Average'),
            below: $row->int('Below'),
            mileage: $row->int('ValuationMileage') ?? $row->int('Mileage') ?? $mileage,
        );
    }

    public function isEmpty(): bool
    {
        return ! $this->retail && ! $this->clean && ! $this->average && ! $this->below;
    }
}
