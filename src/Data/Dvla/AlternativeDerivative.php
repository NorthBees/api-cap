<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Dvla;

use NorthBees\CapApi\Support\CapCode;
use NorthBees\CapApi\Xml\Row;

/**
 * A possible alternative CAP derivative when the DVLA match is ambiguous.
 */
final readonly class AlternativeDerivative
{
    /**
     * @param  array<string, ?string>  $attributes  every raw value returned
     */
    public function __construct(
        public ?int $capId,
        public ?CapCode $capCode,
        public ?string $derivative,
        public array $attributes,
    ) {}

    public static function fromRow(Row $row): self
    {
        return new self(
            capId: $row->int('CAPID'),
            capCode: CapCode::tryFrom($row->string('CAPCODE')),
            derivative: $row->string('DERIVATIVE'),
            attributes: $row->toArray(),
        );
    }
}
