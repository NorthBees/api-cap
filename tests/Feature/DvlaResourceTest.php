<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use NorthBees\CapApi\Cap;
use NorthBees\CapApi\CapCredentials;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Enums\MatchLevelFlag;
use NorthBees\CapApi\Exceptions\CapLookupLimitExceededException;
use NorthBees\CapApi\Exceptions\CapNoMatchException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;
use NorthBees\CapApi\Testing\CapResponse;

it('looks up a VRM and maps the DVLA and CAP blocks', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(capFixture('dvla/DVLALookupVRM.xml'))]);

    $result = app(Cap::class)->dvla()->lookupVrm('ab12 cde');

    expect($result->auditId)->toBe(491911132)
        ->and($result->matchLevel->has(MatchLevelFlag::Cap))->toBeTrue()
        ->and($result->matchLevel->has(MatchLevelFlag::Smmt))->toBeFalse()
        ->and($result->dvla->vin)->toBe('WF0KXXGCBKAA00000')
        ->and($result->dvla->engineNumber)->toBe('AA00000')
        ->and($result->dvla->registrationDate->toDateString())->toBe('2014-09-01')
        ->and($result->dvla->co2)->toBe(169)
        ->and($result->dvla->seats)->toBe(5)
        ->and($result->dvla->isImported)->toBeFalse()
        ->and($result->cap->capId)->toBe(56520)
        ->and((string) $result->cap->capCode)->toBe('FOFO20T3S5HPTM  4')
        ->and($result->cap->database)->toBe(CapDatabase::Car)
        ->and($result->cap->range)->toBe('FOCUS')
        ->and($result->cap->derivative)->toBe('2.0T ST-3 5dr')
        ->and($result->cap->introduced->toDateString())->toBe('2012-05-28')
        ->and($result->cap->transmission)->toBe('Manual');

    Http::assertSent(fn ($request) => $request->header('SOAPAction')[0] === '"https://soap.cap.co.uk/dvla/DVLALookupVRM"'
        && soapParams($request) === ['SubscriberID' => '12345', 'Password' => 'test-password', 'vrm' => 'AB12CDE']);
});

it('looks up a VIN', function () {
    $fake = NorthBees\CapApi\Facades\Cap::fake([
        'dvla.DVLALookupVIN' => CapResponse::dvla(dvla: ['VIN' => 'VIN123'], cap: ['CAPID' => 1, 'VEHICLETYPE' => 'Light Commercial Vehicle'], operation: 'DVLALookupVIN'),
    ]);

    $result = app(Cap::class)->dvla()->lookupVin('vin123');

    expect($result->cap->database)->toBe(CapDatabase::Lcv);
    $fake->assertCalled('dvla.DVLALookupVIN', fn (array $params) => $params === ['SubscriberID' => '12345', 'vin' => 'VIN123']);
});

it('handles the DVLA payload returned as an escaped string', function () {
    $inner = htmlspecialchars('<RESPONSE><SUCCESS>true</SUCCESS><MATCHLEVEL><CAP>1</CAP></MATCHLEVEL><DATA><CAP><CAPID>42</CAPID></CAP></DATA></RESPONSE>', ENT_XML1);
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::envelope(CapService::Dvla, 'DVLALookupVRM', $inner))]);

    expect(app(Cap::class)->dvla()->lookupVrm('X')->cap->capId)->toBe(42);
});

it('throws when the monthly lookup limit is exceeded', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::dvla(success: false, limitExceeded: true))]);

    app(Cap::class)->dvla()->lookupVrm('AB12CDE');
})->throws(CapLookupLimitExceededException::class);

it('throws when nothing matches', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::dvla())]);

    app(Cap::class)->dvla()->lookupVrm('AB12CDE');
})->throws(CapNoMatchException::class);

it('throws the CAP error message on failure', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::dvla(success: false, errorMessage: 'Invalid VRM'))]);

    app(Cap::class)->dvla()->lookupVrm('!!');
})->throws(CapRequestFailedException::class, 'Invalid VRM');

it('maps alternative derivatives', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(CapResponse::dvla(
        cap: ['CAPID' => 1],
        alternativeDerivatives: [['CAPID' => 2, 'DERIVATIVE' => 'Alt 1'], ['CAPID' => 3, 'DERIVATIVE' => 'Alt 2']],
    ))]);

    $result = app(Cap::class)->dvla()->lookupVrm('AB12CDE');

    expect($result->alternativeDerivatives)->toHaveCount(2)
        ->and($result->alternativeDerivatives[1]->capId)->toBe(3)
        ->and($result->alternativeDerivatives[1]->derivative)->toBe('Alt 2');
});

it('uses per-instance credentials', function () {
    Http::fake(['soap.cap.co.uk/*' => Http::response(capFixture('dvla/DVLALookupVRM.xml'))]);

    app(Cap::class)->withCredentials(new CapCredentials(777, 'tenant'))->dvla()->lookupVrm('AB12CDE');

    Http::assertSent(fn ($request) => soapParams($request)['SubscriberID'] === '777' && soapParams($request)['Password'] === 'tenant');
});
