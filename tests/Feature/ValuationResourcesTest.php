<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapNoMatchException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;
use NorthBees\CapApi\Facades\Cap as CapFacade;
use NorthBees\CapApi\Testing\CapResponse;

beforeEach(fn () => CarbonImmutable::setTestNow('2026-09-25 10:00:00'));

it('values a vehicle by CAP ID from the monthly used values', function () {
    $fake = CapFacade::fake(['usedvalues.GetUsedValuation' => CapResponse::dataSet(CapService::UsedValues, 'GetUsedValuation', [
        'Valuation' => [['Retail' => 12500, 'Clean' => 10900, 'Average' => 10100, 'Below' => 9100]],
    ], 'UsedValues')]);

    $valuation = app(Cap::class)->usedValues()->valuation(56520, CarbonImmutable::parse('2014-09-01'), 60000);

    expect($valuation->retail)->toBe(12500)
        ->and($valuation->below)->toBe(9100)
        ->and($valuation->mileage)->toBe(60000);

    $fake->assertCalled('usedvalues.GetUsedValuation', fn (array $params) => $params === [
        'Subscriber_ID' => '12345',
        'Database' => 'CAR',
        'CAPID' => '56520',
        'RegistrationDate' => '2014-09-01T00:00:00',
        'DatasetDate' => '2026-09-25T00:00:00',
        'JustCurrent' => 'true',
        'Mileage' => '60000',
    ]);
});

it('throws when the monthly used values have no rows', function () {
    CapFacade::fake(['usedvalues.*' => CapResponse::dataSet(CapService::UsedValues, 'GetUsedValuation', [])]);

    app(Cap::class)->usedValues()->valuation(1, CarbonImmutable::now(), 1);
})->throws(CapNoMatchException::class);

it('surfaces CAP failure messages such as mileage out of bounds', function () {
    CapFacade::fake(['usedvalues.*' => CapResponse::failure(CapService::UsedValues, 'GetUsedValuation', 'Mileage out of bounds')]);

    app(Cap::class)->usedValues()->valuation(1, CarbonImmutable::now(), 999999);
})->throws(CapRequestFailedException::class, 'Mileage out of bounds');

it('values by year and month from the flat result', function () {
    $fake = CapFacade::fake(['usedvalues.GetUsedValuesForIDYearMonthMileage' => CapResponse::result(CapService::UsedValues, 'GetUsedValuesForIDYearMonthMileage', [
        'Success' => true, 'FailMessage' => '', 'Retail' => 9000, 'Clean' => 8000, 'Average' => 7000, 'Below' => 6000,
    ])]);

    expect(app(Cap::class)->usedValues()->valuationForYearMonth(56520, 2014, 9, 60000)->clean)->toBe(8000);

    $fake->assertCalled('usedvalues.GetUsedValuesForIDYearMonthMileage', fn (array $params) => $params['UsedValuesDate'] === '2026-09-01T00:00:00'
        && array_keys($params) === ['Subscriber_ID', 'Database', 'CAPID', 'UsedValuesDate', 'JustCurrent', 'Year', 'Month', 'Mileage']);
});

it('values a vehicle with CAP Live', function () {
    $fake = CapFacade::fake(['usedvalueslive.GetUsedLive_IdRegDateMileage' => CapResponse::result(CapService::UsedValuesLive, 'GetUsedLive_IdRegDateMileage', [
        'Success' => true,
        'FailMessage' => '',
        'Plate' => ['Year' => 2014, 'Month' => 9, 'Letter' => '64'],
        'ValuationDate' => [
            'Date' => '2026-09-25T00:00:00',
            'IsMonthlyPosition' => false,
            'Valuations' => ['Valuation' => [['Mileage' => 60000, 'Retail' => 12750, 'Clean' => 11000, 'Average' => 10200, 'Below' => 9200]]],
            'Comments' => ['string' => ['Low mileage adjustment applied']],
        ],
    ])]);

    $valuation = app(Cap::class)->usedValuesLive()->valuation(56520, CarbonImmutable::parse('2014-09-01'), 60000);

    expect($valuation->values->retail)->toBe(12750)
        ->and($valuation->values->mileage)->toBe(60000)
        ->and($valuation->valuationDate->toDateString())->toBe('2026-09-25')
        ->and($valuation->isMonthlyPosition)->toBeFalse()
        ->and($valuation->plateLetter)->toBe('64')
        ->and($valuation->comments)->toBe(['Low mileage adjustment applied']);

    $fake->assertCalled('usedvalueslive.GetUsedLive_IdRegDateMileage', fn (array $params) => array_keys($params) === ['subscriberId', 'database', 'capid', 'valuationDate', 'regDate', 'mileage']);
});

it('projects a future valuation', function () {
    $fake = CapFacade::fake(['futurevalues.GetFutureValuation' => CapResponse::dataSet(CapService::FutureValues, 'GetFutureValuation', [
        'Valuation' => [['valuation' => 8450]],
    ], 'FutureValues')]);

    $future = app(Cap::class)->futureValues()->valuation(56520, CarbonImmutable::parse('2024-03-01'), 36, 30000);

    expect($future->value)->toBe(8450)
        ->and($future->monthsToValuation)->toBe(36);

    $fake->assertCalled('futurevalues.GetFutureValuation', fn (array $params) => array_keys($params) === [
        'subscriberId', 'database', 'capid', 'registrationDate', 'datasetDate', 'justCurrent', 'monthsToValuation', 'mileage',
    ]);
});

it('values a VRM in one call with standard equipment', function () {
    $fake = CapFacade::fake(['vrm.VRMValuation' => CapResponse::result(CapService::Vrm, 'VRMValuation', [
        'Success' => true,
        'VRMLookup' => [
            'Success' => true, 'VehicleFound' => true, 'Database' => 'CAR', 'CAPID' => 56520, 'CAPcode' => 'FOFO20T3S5HPTM  4',
            'CAPMan' => 'FORD', 'CAPRange' => 'FOCUS', 'CAPMod' => 'FOCUS HATCHBACK', 'CAPDer' => '2.0T ST-3 5dr',
            'RegisteredDate' => '2014-09-01T00:00:00', 'VinNumber' => 'VIN', 'Colour' => 'BLUE',
        ],
        'Valuation' => ['Success' => true, 'ValuationDateMatch' => true, 'ValuationMileage' => 60000, 'Retail' => 12500, 'Clean' => 10900, 'Average' => 10100, 'Below' => 9100],
        'MileageOutOfBounds' => false,
        'StandardEquipment' => ['Success' => true, 'SEData' => ['SEDataItem' => [
            ['SECode' => 4792, 'SEDescription' => 'Digital clock', 'SECategory' => 'Driver Convenience'],
        ]]],
    ])]);

    $valuation = app(Cap::class)->vrm()->valuationByVrm('ab12 cde', 60000, withStandardEquipment: true);

    expect($valuation->lookup->capId)->toBe(56520)
        ->and($valuation->lookup->capCode->transmission())->toBe('Manual')
        ->and($valuation->lookup->registeredDate->toDateString())->toBe('2014-09-01')
        ->and($valuation->valuation->retail)->toBe(12500)
        ->and($valuation->valuation->mileage)->toBe(60000)
        ->and($valuation->standardEquipment[0]->description)->toBe('Digital clock');

    $fake->assertCalled('vrm.VRMValuation', fn (array $params) => $params === [
        'SubscriberID' => '12345', 'VRM' => 'AB12CDE', 'Mileage' => '60000', 'StandardEquipmentRequired' => 'true',
    ]);
});

it('throws no-match when the VRM service cannot find the vehicle', function () {
    CapFacade::fake(['vrm.*' => CapResponse::result(CapService::Vrm, 'VRMValuation', [
        'Success' => false, 'VRMLookup' => ['Success' => false, 'VehicleFound' => false], 'FailReason' => 'Vehicle not found',
    ])]);

    app(Cap::class)->vrm()->valuationByVrm('ZZ99ZZZ', 1000);
})->throws(CapNoMatchException::class);

it('values by CAP ID via the VRM service', function () {
    $fake = CapFacade::fake(['vrm.CAPIDValuation' => CapResponse::result(CapService::Vrm, 'CAPIDValuation', [
        'Success' => true,
        'CAPIDLookup' => ['Success' => true, 'IDFound' => true, 'CAPID' => 56520],
        'Valuation' => ['Success' => true, 'Retail' => 1],
    ])]);

    expect(app(Cap::class)->vrm()->valuationByCapId(56520, CarbonImmutable::parse('2014-09-01'), 1000)->lookup->found)->toBeTrue();

    $fake->assertCalled('vrm.CAPIDValuation', fn (array $params) => array_keys($params) === ['SubscriberID', 'Database', 'CAPID', 'RegisteredDate', 'Mileage', 'StandardEquipmentRequired']);
});
