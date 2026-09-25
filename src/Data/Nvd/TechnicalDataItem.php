<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\Row;

final readonly class TechnicalDataItem
{
    public function __construct(
        public int $code,
        public string $description,
        public ?string $category,
        public ?string $value,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self(
            code: (int) $row->int('Tech_TechCode'),
            description: (string) $row->string('DT_LongDescription'),
            category: $row->string('Dc_Description'),
            value: $row->string('tech_value_string'),
        );
    }
}
