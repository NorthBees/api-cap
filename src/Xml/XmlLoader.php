<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Xml;

use DOMDocument;
use NorthBees\CapApi\Exceptions\CapInvalidResponseException;

/**
 * Loads untrusted XML safely: no network access, and no DTDs (XXE protection).
 */
final class XmlLoader
{
    public static function load(string $xml): DOMDocument
    {
        $xml = trim($xml);

        if ($xml === '') {
            throw new CapInvalidResponseException('CAP returned an empty response.');
        }

        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new CapInvalidResponseException('CAP response contained a DTD, which is not allowed.');
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || $document->documentElement === null) {
            $reason = isset($errors[0]) ? trim($errors[0]->message) : 'unknown error';

            throw new CapInvalidResponseException("CAP response is not valid XML ({$reason}): ".self::snippet($xml));
        }

        return $document;
    }

    /**
     * A short excerpt of a response body for exception messages.
     */
    public static function snippet(string $body, int $length = 200): string
    {
        $body = preg_replace('/\s+/', ' ', $body) ?? $body;

        return mb_strlen($body) > $length ? mb_substr($body, 0, $length).'…' : $body;
    }
}
