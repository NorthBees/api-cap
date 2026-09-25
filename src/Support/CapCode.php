<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Support;

/**
 * A CAP code, e.g. "FOFO20T3S5HPTM  4". Character 14 encodes the transmission (M/A).
 */
final readonly class CapCode
{
    public function __construct(public string $value) {}

    public static function tryFrom(?string $value): ?self
    {
        $value = $value === null ? '' : rtrim($value);

        return $value === '' ? null : new self($value);
    }

    /**
     * "Manual", "Automatic" or null when the code does not say.
     */
    public function transmission(): ?string
    {
        return match (strtoupper(substr($this->value, 13, 1))) {
            'M' => 'Manual',
            'A' => 'Automatic',
            default => null,
        };
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
