<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use NorthBees\CapApi\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit', 'Live');

/**
 * Load a recorded CAP response from `tests/fixtures`.
 */
function capFixture(string $file): string
{
    return (string) file_get_contents(__DIR__.'/fixtures/'.$file);
}

/**
 * The operation's child elements from a sent SOAP request, in order.
 *
 * @return array<string, string>
 */
function soapParams(Request $request): array
{
    $document = new DOMDocument;
    $document->loadXML($request->body());
    $operation = $document->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body')->item(0)->firstElementChild;

    $params = [];

    foreach ($operation->childNodes as $node) {
        if ($node instanceof DOMElement) {
            $params[$node->localName] = $node->textContent;
        }
    }

    return $params;
}
