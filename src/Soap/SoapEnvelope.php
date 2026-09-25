<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Soap;

use DateTimeInterface;
use DOMDocument;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Xml\Value;

/**
 * Builds SOAP 1.1 request envelopes.
 *
 * Parameters must be passed in WSDL sequence order: .NET's XmlSerializer
 * silently ignores out-of-order elements and uses defaults instead.
 */
final class SoapEnvelope
{
    public const string SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';

    /**
     * @param  array<string, bool|int|float|string|DateTimeInterface|null>  $params  null values are omitted
     */
    public static function build(CapService $service, string $operation, array $params): string
    {
        $document = new DOMDocument('1.0', 'utf-8');

        $envelope = $document->createElementNS(self::SOAP_NAMESPACE, 'soap:Envelope');
        $document->appendChild($envelope);

        $body = $document->createElementNS(self::SOAP_NAMESPACE, 'soap:Body');
        $envelope->appendChild($body);

        $request = $document->createElementNS($service->namespace(), $operation);
        $body->appendChild($request);

        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }

            $element = $document->createElementNS($service->namespace(), $name);
            $element->appendChild($document->createTextNode(Value::toSoap($value)));
            $request->appendChild($element);
        }

        return (string) $document->saveXML();
    }
}
