<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Taxonomy;

use NorthBees\CapApi\Xml\Row;

/**
 * A CAP range, e.g. "FOCUS". Sits between manufacturer and model.
 */
final readonly class Range
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public int $code,
        public string $name,
        public array $attributes,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self((int) $row->int('CRan_Code'), (string) $row->string('CRan_Name'), $row->toArray());
    }
}
