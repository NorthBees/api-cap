<?php

declare(strict_types=1);

use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapSoapFaultException;
use NorthBees\CapApi\Facades\Cap as CapFacade;
use NorthBees\CapApi\Testing\CapResponse;

it('faults unfaked operations loudly', function () {
    CapFacade::fake();

    app(Cap::class)->vehicles()->manufacturers();
})->throws(CapSoapFaultException::class, 'vehicles.GetCapMan');

it('passes request params to closure responses and records calls without the password', function () {
    $fake = CapFacade::fake([
        'vehicles.GetCapRange' => fn (array $params) => CapResponse::dataSet(CapService::Vehicles, 'GetCapRange', ['Table' => [
            ['CRan_Code' => (int) $params['manCode'], 'CRan_Name' => 'RANGE '.$params['manCode']],
        ]]),
    ]);

    expect(app(Cap::class)->vehicles()->ranges(99)->first()->name)->toBe('RANGE 99');

    $fake->assertCalledTimes('vehicles.GetCapRange', 1);
    $fake->assertNotCalled('vehicles.GetCapMan');
    expect(json_encode($fake->calls()))->not->toContain('test-password');
});
