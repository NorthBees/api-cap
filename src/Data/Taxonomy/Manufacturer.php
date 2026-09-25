<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Taxonomy;

use NorthBees\CapApi\Xml\Row;

final readonly class Manufacturer
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
        return new self((int) $row->int('CMan_Code'), (string) $row->string('CMan_Name'), $row->toArray());
    }
}
