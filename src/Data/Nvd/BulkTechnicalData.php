<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\Row;

/**
 * One vehicle's row from GetBulkTechnicalData. Columns depend on the requested
 * tech items, so values are exposed through the underlying row.
 */
final readonly class BulkTechnicalData
{
    public function __construct(public ?int $capId, public Row $row) {}

    public static function fromRow(Row $row): self
    {
        return new self($row->int('CAPID') ?? $row->int('capid'), $row);
    }
}
