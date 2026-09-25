<?php

declare(strict_types=1);

use NorthBees\CapApi\Cap;
use NorthBees\CapApi\CapCredentials;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapMissingCredentialsException;
use NorthBees\CapApi\Images\ImageUrlBuilder;
use NorthBees\CapApi\Support\CapCode;

it('resolves a scoped client with config credentials', function () {
    $cap = app(Cap::class);

    expect($cap)->toBe(app('cap'))
        ->and($cap->credentials()->subscriberId)->toBe(12345)
        ->and($cap->database())->toBe(CapDatabase::Car);
});

it('returns new instances from withCredentials and withDatabase', function () {
    $cap = app(Cap::class);
    $tenant = $cap->withCredentials(new CapCredentials(999, 'tenant-secret'))->withDatabase(CapDatabase::Lcv);

    expect($tenant)->not->toBe($cap)
        ->and($tenant->credentials()->subscriberId)->toBe(999)
        ->and($tenant->database())->toBe(CapDatabase::Lcv)
        ->and($cap->credentials()->subscriberId)->toBe(12345)
        ->and($cap->database())->toBe(CapDatabase::Car);
});

it('throws when no credentials are available', function () {
    config()->set('cap.password', null);
    app()->forgetScopedInstances();

    expect(app(Cap::class)->hasCredentials())->toBeFalse();

    app(Cap::class)->credentials();
})->throws(CapMissingCredentialsException::class);

it('redacts the password when dumped', function () {
    $credentials = new CapCredentials(12345, 'super-secret');

    expect(print_r($credentials, true))->not->toContain('super-secret')
        ->and(var_export($credentials->__debugInfo(), true))->not->toContain('super-secret');
});

it('builds signed image URLs', function () {
    $builder = new ImageUrlBuilder(new CapCredentials(12345, 'secret'), 'https://soap.cap.co.uk/images/VehicleImage.aspx');
    $hash = strtoupper(md5('12345secretCAR56520'));

    $url = $builder->url(56520, CapDatabase::Car, 1280, 720, viewpoint: 3);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'SUBID' => '12345',
        'HASHCODE' => $hash,
        'DB' => 'CAR',
        'CAPID' => '56520',
        'WIDTH' => '1280',
        'HEIGHT' => '720',
        'VIEWPOINT' => '3',
    ]);

    parse_str((string) parse_url($builder->url(1, CapDatabase::Lcv, viewpoint: 3), PHP_URL_QUERY), $lcv);

    expect($lcv['VIEWPOINT'])->toBe('');
});

it('reads the transmission from the CAP code', function (string $code, ?string $transmission) {
    expect(CapCode::tryFrom($code)?->transmission())->toBe($transmission);
})->with([
    ['FOFO20T3S5HPTM  4   ', 'Manual'],
    ['FOFO20T3S5HPTA  4', 'Automatic'],
    ['SHORT', null],
]);

it('maps services to endpoints, namespaces and credential casing', function () {
    expect(CapService::Dvla->path())->toBe('dvla/capdvla.asmx')
        ->and(CapService::Nvd->soapAction('GetTechnicalData'))->toBe('"https://soap.cap.co.uk/nvd/GetTechnicalData"')
        ->and(CapService::Dvla->credentialElements())->toBe(['SubscriberID', 'Password'])
        ->and(CapService::UsedValues->credentialElements())->toBe(['Subscriber_ID', 'Password'])
        ->and(CapService::UsedValuesLive->credentialElements())->toBe(['subscriberId', 'password'])
        ->and(CapService::fromPath('/usedvalueslive/capusedvalueslive.asmx'))->toBe(CapService::UsedValuesLive);
});
