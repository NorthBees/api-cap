<?php

declare(strict_types=1);

use NorthBees\CapApi\Cap;
use NorthBees\CapApi\CapCredentials;

/*
 * Live contract checks against the real CAP services. Excluded by default; run with:
 * CAP_LIVE=1 CAP_SUBSCRIBER_ID=... CAP_PASSWORD=... vendor/bin/pest --group=live
 */

beforeEach(function () {
    if (! env('CAP_LIVE')) {
        $this->markTestSkipped('Set CAP_LIVE=1 with CAP_SUBSCRIBER_ID and CAP_PASSWORD to run live checks.');
    }

    Illuminate\Support\Facades\Http::allowStrayRequests();

    $this->cap = app(Cap::class)->withCredentials(new CapCredentials((int) env('CAP_SUBSCRIBER_ID'), (string) env('CAP_PASSWORD')));
});

it('lists manufacturers', function () {
    expect($this->cap->vehicles()->manufacturers())->not->toBeEmpty();
})->group('live');

it('walks the taxonomy down to derivatives', function () {
    $manufacturer = $this->cap->vehicles()->manufacturers()->firstWhere('name', 'FORD');
    $range = $this->cap->vehicles()->ranges($manufacturer->code)->first();
    $model = $this->cap->vehicles()->models($range->code)->first();

    expect($model->code)->toBeGreaterThan(0)
        ->and($this->cap->vehicles()->derivatives($model->code)->first()->capId)->toBeGreaterThan(0);
})->group('live');

it('fetches technical data and standard equipment', function () {
    $capId = (int) env('CAP_LIVE_CAPID', 56520);

    expect($this->cap->nvd()->technicalData($capId, justCurrent: true)->isEmpty())->toBeFalse()
        ->and($this->cap->nvd()->standardEquipment($capId, justCurrent: true))->not->toBeEmpty();
})->group('live');

it('looks up a VRM (uses one DVLA lookup)', function () {
    if (! env('CAP_LIVE_VRM')) {
        $this->markTestSkipped('Set CAP_LIVE_VRM to spend a DVLA lookup.');
    }

    expect($this->cap->dvla()->lookupVrm((string) env('CAP_LIVE_VRM'))->cap?->capId)->toBeGreaterThan(0);
})->group('live');
