<?php

declare(strict_types=1);

namespace NorthBees\CapApi;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use NorthBees\CapApi\Soap\SoapTransport;

/**
 * This is the service provider for the CAP API package.
 */
class CapServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cap.php' => config_path('cap.php'),
            ], 'cap.config');
        }
    }

    /**
     * Register any package services.
     *
     * Scoped rather than singleton so queue workers and Octane never share a
     * client across requests or tenants.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cap.php', 'cap');

        $this->app->scoped(Cap::class, function (Application $app): Cap {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('cap', []);

            return new Cap(new SoapTransport($config), $config);
        });

        $this->app->alias(Cap::class, 'cap');
    }
}
