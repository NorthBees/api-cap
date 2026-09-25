<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Taxonomy;

use NorthBees\CapApi\Xml\Row;

/**
 * A CAP model, e.g. "FOCUS HATCHBACK (2011 - 2014)". Sits between range and derivative.
 */
final readonly class Model
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
        return new self((int) $row->int('CMod_Code'), (string) $row->string('CMod_Name'), $row->toArray());
    }
}
