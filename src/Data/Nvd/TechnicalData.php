<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\DataSetReader;

/**
 * Technical data for one CAP ID, keyed by CAP tech code.
 */
final readonly class TechnicalData
{
    /** CAP tech code for CO2 (g/km). */
    public const int CO2 = 67;

    /** CAP tech code for insurance group (1-50). */
    public const int INSURANCE_GROUP = 133;

    /**
     * @param  array<int, TechnicalDataItem>  $items  keyed by tech code
     */
    public function __construct(public int $capId, public array $items) {}

    public static function fromDataSet(int $capId, DataSetReader $reader): self
    {
        $items = [];

        foreach ($reader->rows() as $row) {
            $item = TechnicalDataItem::fromRow($row);
            $items[$item->code] = $item;
        }

        return new self($capId, $items);
    }

    public function get(int $code): ?TechnicalDataItem
    {
        return $this->items[$code] ?? null;
    }

    public function value(int $code): ?string
    {
        return $this->get($code)?->value;
    }

    /**
     * @return array<string, list<TechnicalDataItem>>
     */
    public function byCategory(): array
    {
        $grouped = [];

        foreach ($this->items as $item) {
            $grouped[$item->category ?? 'Other'][] = $item;
        }

        return $grouped;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
