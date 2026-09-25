<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Xml;

use Carbon\CarbonImmutable;
use DOMElement;
use Throwable;

/**
 * One record from a CAP response, with null-safe typed accessors.
 *
 * CAP is inconsistent with element casing (Tech_TechCode vs tech_value_string,
 * CAPID vs capid), so key lookups fall back to a case-insensitive match.
 */
final readonly class Row
{
    private const string XSI_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * @param  array<string, ?string>  $values
     */
    public function __construct(private array $values) {}

    /**
     * Build a row from the leaf child elements of an element.
     */
    public static function fromElement(?DOMElement $element): self
    {
        if ($element === null) {
            return new self([]);
        }

        $values = [];

        foreach ($element->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $values[$child->localName] = $child->getAttributeNS(self::XSI_NAMESPACE, 'nil') === 'true'
                ? null
                : $child->textContent;
        }

        return new self($values);
    }

    public function has(string $key): bool
    {
        return $this->resolveKey($key) !== null;
    }

    /**
     * The trimmed string value, or null when missing, nil or blank.
     */
    public function string(string $key): ?string
    {
        $resolved = $this->resolveKey($key);
        $value = $resolved === null ? null : $this->values[$resolved];

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function int(string $key): ?int
    {
        $value = $this->string($key);

        return $value !== null && is_numeric($value) ? (int) round((float) $value) : null;
    }

    public function float(string $key): ?float
    {
        $value = $this->string($key);

        return $value !== null && is_numeric($value) ? (float) $value : null;
    }

    public function bool(string $key): ?bool
    {
        return match (strtolower((string) $this->string($key))) {
            'true', '1', 'yes', 'y' => true,
            'false', '0', 'no', 'n' => false,
            default => null,
        };
    }

    /**
     * Parse a date in either CAP's compact Ymd form or an ISO/xs:dateTime form.
     * .NET's DateTime.MinValue (0001-01-01) is treated as null.
     */
    public function date(string $key): ?CarbonImmutable
    {
        $value = $this->string($key);

        if ($value === null || str_starts_with($value, '0001-01-01')) {
            return null;
        }

        try {
            if (preg_match('/^\d{8}$/', $value) === 1) {
                return CarbonImmutable::createFromFormat('!Ymd', $value) ?: null;
            }

            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $value, $matches) === 1) {
                return CarbonImmutable::createFromFormat('!d/m/Y', "{$matches[1]}/{$matches[2]}/{$matches[3]}") ?: null;
            }

            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, ?string>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    private function resolveKey(string $key): ?string
    {
        if (array_key_exists($key, $this->values)) {
            return $key;
        }

        foreach (array_keys($this->values) as $candidate) {
            if (strcasecmp($candidate, $key) === 0) {
                return $candidate;
            }
        }

        return null;
    }
}
