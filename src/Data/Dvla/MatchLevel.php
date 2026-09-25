<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Dvla;

use NorthBees\CapApi\Enums\MatchLevelFlag;
use NorthBees\CapApi\Xml\Row;

/**
 * Which data sources matched a DVLA lookup.
 */
final readonly class MatchLevel
{
    /**
     * @param  array<string, bool>  $flags  keyed by MatchLevelFlag value
     */
    public function __construct(public array $flags) {}

    public static function fromRow(Row $row): self
    {
        $flags = [];

        foreach (MatchLevelFlag::cases() as $flag) {
            $flags[$flag->value] = $row->bool($flag->value) ?? false;
        }

        return new self($flags);
    }

    public function has(MatchLevelFlag $flag): bool
    {
        return $this->flags[$flag->value] ?? false;
    }
}
