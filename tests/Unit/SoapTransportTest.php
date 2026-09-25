<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapConnectionException;
use NorthBees\CapApi\Exceptions\CapInvalidResponseException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;
use NorthBees\CapApi\Exceptions\CapSoapFaultException;
use NorthBees\CapApi\Testing\CapResponse;

$manufacturers = fn () => CapResponse::dataSet(CapService::Vehicles, 'GetCapMan', ['Table' => [['CMan_Code' => 2514, 'CMan_Name' => 'FORD']]]);

it('does not retry SOAP faults', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::fault('Server was unable to process request.'), 500)]);

    try {
        app(Cap::class)->vehicles()->manufacturers();
        $this->fail('Expected a SOAP fault.');
    } catch (CapSoapFaultException $exception) {
        expect($exception->faultString)->toBe('Server was unable to process request.')
            ->and($exception->operation)->toBe('GetCapMan');
    }

    Http::assertSentCount(1);
});

it('retries 503 responses then succeeds', function () use ($manufacturers) {
    Http::fake(['soap.cap.co.uk/*' => Http::sequence()->push('', 503)->push($manufacturers(), 200)]);

    expect(app(Cap::class)->vehicles()->manufacturers())->toHaveCount(1);

    Http::assertSentCount(2);
});

it('retries connection failures and then gives up', function () {
    Http::fake(['soap.cap.co.uk/*' => fn () => throw new ConnectionException('timed out')]);

    app(Cap::class)->vehicles()->manufacturers();
})->throws(CapConnectionException::class);

it('never retries DVLA lookups on server errors', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response('', 503)]);

    try {
        app(Cap::class)->dvla()->lookupVrm('AB12CDE');
    } catch (CapConnectionException) {
    }

    Http::assertSentCount(1);
});

it('raises Success=false as a request failure', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::failure(CapService::Vehicles, 'GetCapMan', 'Invalid subscriber'))]);

    app(Cap::class)->vehicles()->manufacturers();
})->throws(CapRequestFailedException::class, 'Invalid subscriber');

it('raises invalid responses when the result element is missing', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body /></soap:Envelope>')]);

    app(Cap::class)->vehicles()->manufacturers();
})->throws(CapInvalidResponseException::class, 'GetCapManResult');

it('sends the SOAPAction header and posts to the service endpoint', function () use ($manufacturers) {
    Http::fake(['soap.cap.co.uk/*' => Http::response($manufacturers())]);

    app(Cap::class)->vehicles()->manufacturers();

    Http::assertSent(fn ($request) => $request->url() === 'https://soap.cap.co.uk/vehicles/capvehicles.asmx'
        && $request->header('SOAPAction')[0] === '"https://soap.cap.co.uk/vehicles/GetCapMan"'
        && str_starts_with($request->header('Content-Type')[0], 'text/xml'));
});

it('logs call metadata without the password', function () use ($manufacturers) {
    config()->set('cap.logging.enabled', true);
    app()->forgetScopedInstances();
    Http::fake(['soap.cap.co.uk/*' => Http::response($manufacturers())]);

    $logged = [];
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->andReturnUsing(function (string $message, array $context) use (&$logged) {
        $logged[] = $message.json_encode($context);
    });

    app(Cap::class)->vehicles()->manufacturers();

    expect($logged)->toHaveCount(1)
        ->and($logged[0])->toContain('GetCapMan')->not->toContain('test-password');
});

it('keeps the password out of exception messages', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response('<not-xml', 200)]);

    try {
        app(Cap::class)->vehicles()->manufacturers();
    } catch (CapInvalidResponseException $exception) {
        expect($exception->getMessage())->not->toContain('test-password');
    }
});
