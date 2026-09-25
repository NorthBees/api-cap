<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Taxonomy;

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Xml\Row;

/**
 * A CAP derivative. Its CAP ID is the key used by every other CAP service.
 */
final readonly class Derivative
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public int $capId,
        public string $name,
        public ?string $modelYearRef,
        public ?CarbonImmutable $introduced,
        public ?CarbonImmutable $discontinued,
        public array $attributes,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self(
            capId: (int) $row->int('CDer_ID'),
            name: (string) $row->string('CDer_Name'),
            modelYearRef: $row->string('ModelYearRef'),
            introduced: $row->date('CDer_Introduced'),
            discontinued: $row->date('CDer_Discontinued'),
            attributes: $row->toArray(),
        );
    }
}
