<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Data\Nvd\TechnicalData;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Facades\Cap as CapFacade;
use NorthBees\CapApi\Testing\CapResponse;

it('fetches standard equipment', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(capFixture('nvd/GetStandardEquipment.xml'))]);

    $equipment = app(Cap::class)->nvd()->standardEquipment(56520, CarbonImmutable::parse('2014-09-01'));

    expect($equipment)->toHaveCount(3)
        ->and($equipment->first()->code)->toBe(80351)
        ->and($equipment->first()->description)->toBe("'Ford Power' starter button")
        ->and($equipment->last()->category)->toBe('Exterior Features');

    Http::assertSent(fn ($request) => soapParams($request) === [
        'subscriberId' => '12345',
        'password' => 'test-password',
        'database' => 'CAR',
        'capid' => '56520',
        'seDate' => '2014-09-01T00:00:00',
        'justCurrent' => 'false',
    ]);
});

it('fetches technical data keyed by tech code', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(capFixture('nvd/GetTechnicalData.xml'))]);

    $technicalData = app(Cap::class)->nvd()->technicalData(56520, CarbonImmutable::parse('2014-09-01'));

    expect($technicalData->value(TechnicalData::CO2))->toBe('169')
        ->and($technicalData->get(TechnicalData::INSURANCE_GROUP)->category)->toBe('General')
        ->and(array_keys($technicalData->byCategory()))->toBe(['Emissions', 'General']);

    Http::assertSent(fn ($request) => array_keys(soapParams($request)) === ['subscriberId', 'password', 'database', 'capid', 'techDate', 'justCurrent']);
});

it('fetches bulk technical data for several CAP IDs', function () {
    $fake = CapFacade::fake(['nvd.GetBulkTechnicalData' => CapResponse::dataSet(CapService::Nvd, 'GetBulkTechnicalData', ['Tech_Table' => [
        ['CAPID' => 1, 'CO2' => 120, 'Basic' => '15000.00'],
        ['CAPID' => 2, 'CO2' => 140, 'Basic' => '18000.00'],
    ]], 'TechData')]);

    $rows = app(Cap::class)->nvd()->bulkTechnicalData([1, 2], CarbonImmutable::parse('2026-01-15'), 'CO2');

    expect($rows)->toHaveCount(2)
        ->and($rows->last()->capId)->toBe(2)
        ->and($rows->last()->row->float('Basic'))->toBe(18000.0);

    $fake->assertCalled('nvd.GetBulkTechnicalData', fn (array $params) => $params['capidList'] === '1,2'
        && $params['specDateList'] === '2026/01/15,2026/01/15'
        && $params['techDataList'] === 'CO2');
});

it('returns the options bundle tables', function () {
    CapFacade::fake(['nvd.GetCapOptionsBundle' => CapResponse::dataSet(CapService::Nvd, 'GetCapOptionsBundle', [
        'Options' => [['Opt_OptionCode' => 1]],
        'Pack' => [['Pack_OptionCode' => 2]],
    ])]);

    $bundle = app(Cap::class)->nvd()->optionsBundle(56520);

    expect($bundle->tableNames())->toBe(['Options', 'Pack'])
        ->and($bundle->table('Pack')[0]->int('Pack_OptionCode'))->toBe(2);
});

it('fetches P11D data', function () {
    CapFacade::fake(['nvd.GetP11DData' => CapResponse::dataSet(CapService::Nvd, 'GetP11DData', [
        'CO2_Table' => [['CO2' => 169]],
        'Euro_Emissions_Table' => [['Euro_Emissions' => 'EURO 5']],
        'CC_Table' => [['CC' => 1999]],
        'FuelType_Table' => [['FuelType' => 'P']],
    ], 'P11D')]);

    $p11d = app(Cap::class)->nvd()->p11d(56520, 2024, 2025, 2026);

    expect($p11d->co2)->toBe(169)
        ->and($p11d->euroEmissions)->toBe('EURO 5')
        ->and($p11d->engineCapacity)->toBe(1999)
        ->and($p11d->fuelType)->toBe('P');
});
