<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Soap\SoapEnvelope;

it('builds a namespaced SOAP 1.1 envelope with params in the given order', function () {
    $xml = SoapEnvelope::build(CapService::Nvd, 'GetTechnicalData', [
        'subscriberId' => 12345,
        'password' => 'secret',
        'database' => 'CAR',
        'capid' => 56520,
        'techDate' => CarbonImmutable::parse('2026-09-25 13:45:00'),
        'justCurrent' => false,
    ]);

    $document = new DOMDocument;
    $document->loadXML($xml);
    $operation = $document->getElementsByTagNameNS(SoapEnvelope::SOAP_NAMESPACE, 'Body')->item(0)->firstElementChild;

    expect($operation->localName)->toBe('GetTechnicalData')
        ->and($operation->namespaceURI)->toBe('https://soap.cap.co.uk/nvd');

    $children = [];
    foreach ($operation->childNodes as $node) {
        if ($node instanceof DOMElement) {
            $children[$node->localName] = $node->textContent;
            expect($node->namespaceURI)->toBe('https://soap.cap.co.uk/nvd');
        }
    }

    expect($children)->toBe([
        'subscriberId' => '12345',
        'password' => 'secret',
        'database' => 'CAR',
        'capid' => '56520',
        'techDate' => '2026-09-25T13:45:00',
        'justCurrent' => 'false',
    ]);
});

it('omits null params and escapes XML special characters', function () {
    $xml = SoapEnvelope::build(CapService::Vehicles, 'GetCapMan', [
        'password' => 'p&ss<word>',
        'bodyStyleFilter' => null,
    ]);

    expect($xml)->toContain('<password>p&amp;ss&lt;word&gt;</password>')
        ->not->toContain('bodyStyleFilter');
});
