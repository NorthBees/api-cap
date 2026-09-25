<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Xml;

use DateTimeInterface;

/**
 * Serialises PHP values to their SOAP (xs:*) text form.
 */
final class Value
{
    public static function toSoap(bool|int|float|string|DateTimeInterface $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof DateTimeInterface => $value->format('Y-m-d\TH:i:s'),
            default => (string) $value,
        };
    }
}
