<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Tests;

use Illuminate\Support\Facades\Http;
use NorthBees\CapApi\CapServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app)
    {
        return [
            CapServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cap.subscriber_id', 12345);
        $app['config']->set('cap.password', 'test-password');
        $app['config']->set('cap.retry.sleep_ms', 0);
    }
}
