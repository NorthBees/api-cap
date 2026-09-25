<?php

declare(strict_types=1);

use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Facades\Cap as CapFacade;
use NorthBees\CapApi\Testing\CapResponse;

it('lists manufacturers', function () {
    $fake = CapFacade::fake([
        'vehicles.GetCapMan' => CapResponse::dataSet(CapService::Vehicles, 'GetCapMan', ['Table' => [
            ['CMan_Code' => 2514, 'CMan_Name' => 'FORD'],
            ['CMan_Code' => 3015, 'CMan_Name' => 'VAUXHALL'],
        ]]),
    ]);

    $manufacturers = app(Cap::class)->vehicles()->manufacturers();

    expect($manufacturers)->toHaveCount(2)
        ->and($manufacturers->first()->code)->toBe(2514)
        ->and($manufacturers->first()->name)->toBe('FORD');

    $fake->assertCalled('vehicles.GetCapMan', fn (array $params) => $params === [
        'subscriberId' => '12345',
        'database' => 'CAR',
        'justCurrentManufacturers' => 'true',
    ]);
});

it('uses the include-on-runout operations when asked', function () {
    $fake = CapFacade::fake(['vehicles.*' => CapResponse::dataSet(CapService::Vehicles, 'GetCapRange_IncludeOnRunout', ['Table' => [['CRan_Code' => 256, 'CRan_Name' => 'FOCUS']]])]);

    $ranges = app(Cap::class)->vehicles()->ranges(2514, justCurrent: false, includeOnRunout: true, database: CapDatabase::Lcv);

    expect($ranges->first()->name)->toBe('FOCUS');
    $fake->assertCalled('vehicles.GetCapRange_IncludeOnRunout', fn (array $params) => $params === [
        'subscriberId' => '12345',
        'database' => 'LCV',
        'manCode' => '2514',
        'justCurrentRanges' => 'false',
    ]);
});

it('lists models with the manRanCode parameters in WSDL order', function () {
    $fake = CapFacade::fake(['vehicles.GetCapMod' => CapResponse::dataSet(CapService::Vehicles, 'GetCapMod', ['Table' => [['CMod_Code' => 53847, 'CMod_Name' => 'FOCUS HATCHBACK']]])]);

    expect(app(Cap::class)->vehicles()->models(256)->first()->code)->toBe(53847);

    $fake->assertCalled('vehicles.GetCapMod', fn (array $params) => array_keys($params) === ['subscriberId', 'database', 'manRanCode', 'manRanCodeIsMan', 'justCurrentModels']);
});

it('lists derivatives for a model and for a range', function () {
    $rows = ['Table' => [['CDer_ID' => 56520, 'CDer_Name' => '2.0T ST-3 5dr', 'ModelYearRef' => '2012', 'CDer_Introduced' => '2012-05-28T00:00:00+01:00', 'CDer_Discontinued' => null]]];
    $fake = CapFacade::fake([
        'vehicles.GetCapDer' => CapResponse::dataSet(CapService::Vehicles, 'GetCapDer', $rows),
        'vehicles.GetCapDerFromRange' => CapResponse::dataSet(CapService::Vehicles, 'GetCapDerFromRange', $rows),
    ]);

    $derivative = app(Cap::class)->vehicles()->derivatives(53847)->first();

    expect($derivative->capId)->toBe(56520)
        ->and($derivative->introduced->toDateString())->toBe('2012-05-28')
        ->and($derivative->discontinued)->toBeNull()
        ->and(app(Cap::class)->vehicles()->derivativesForRange(256))->toHaveCount(1);

    $fake->assertCalled('vehicles.GetCapDer', fn (array $params) => $params['modCode'] === '53847');
    $fake->assertCalled('vehicles.GetCapDerFromRange', fn (array $params) => $params['ranCode'] === '256');
});

it('describes a CAP ID', function () {
    CapFacade::fake(['vehicles.GetCapDescriptionFromId' => CapResponse::dataSet(CapService::Vehicles, 'GetCapDescriptionFromId', ['Table' => [[
        'CVehicle_ManText' => 'FORD',
        'CVehicle_ModText' => 'FOCUS HATCHBACK',
        'CVehicle_ShortModText' => 'FOCUS',
        'CVehicle_DerText' => '2.0T ST-3 5dr',
        'CVehicle_ShortModID' => 53847,
    ]]])]);

    $description = app(Cap::class)->vehicles()->description(56520);

    expect($description->manufacturer)->toBe('FORD')
        ->and($description->modelShort)->toBe('FOCUS')
        ->and($description->modelId)->toBe(53847);
});

it('returns null for an unknown CAP ID', function () {
    CapFacade::fake(['vehicles.*' => CapResponse::dataSet(CapService::Vehicles, 'GetCapDescriptionFromId', [])]);

    expect(app(Cap::class)->vehicles()->description(1))->toBeNull();
});

it('converts between CAP ID and CAP code', function () {
    $fake = CapFacade::fake([
        'vehicles.GetCapcodeFromCapid' => CapResponse::dataSet(CapService::Vehicles, 'GetCapcodeFromCapid', ['Table' => [['CDer_CAPcode' => 'FOFO20T3S5HPTM  4']]]),
        'vehicles.GetCapidFromCapcode' => CapResponse::dataSet(CapService::Vehicles, 'GetCapidFromCapcode', ['Table' => [['CDer_ID' => 56520]]]),
    ]);

    expect(app(Cap::class)->vehicles()->capCodeFor(56520))->toBe('FOFO20T3S5HPTM  4')
        ->and(app(Cap::class)->vehicles()->capIdFor('FOFO20T3S5HPTM  4'))->toBe(56520);

    $fake->assertCalled('vehicles.GetCapidFromCapcode', fn (array $params) => $params['caPcode'] === 'FOFO20T3S5HPTM  4');
});
