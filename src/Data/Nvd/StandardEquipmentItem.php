<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\Row;

final readonly class StandardEquipmentItem
{
    public function __construct(
        public int $code,
        public string $description,
        public ?string $category,
    ) {}

    /**
     * From an NVD GetStandardEquipment row (Se_OptionCode / Do_Description / Dc_Description).
     */
    public static function fromRow(Row $row): self
    {
        return new self((int) $row->int('Se_OptionCode'), (string) $row->string('Do_Description'), $row->string('Dc_Description'));
    }

    /**
     * From a VRM service SEDataItem (SECode / SEDescription / SECategory).
     */
    public static function fromVrmRow(Row $row): self
    {
        return new self((int) $row->int('SECode'), (string) $row->string('SEDescription'), $row->string('SECategory'));
    }
}
