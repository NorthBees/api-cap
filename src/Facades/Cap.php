<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Facades;

use Illuminate\Support\Facades\Facade;
use NorthBees\CapApi\Testing\CapFake;

/**
 * @method static \NorthBees\CapApi\Cap withCredentials(\NorthBees\CapApi\CapCredentials $credentials)
 * @method static \NorthBees\CapApi\Cap withDatabase(\NorthBees\CapApi\Enums\CapDatabase $database)
 * @method static \NorthBees\CapApi\CapCredentials credentials()
 * @method static bool hasCredentials()
 * @method static \NorthBees\CapApi\Enums\CapDatabase database()
 * @method static \NorthBees\CapApi\Resources\DvlaResource dvla()
 * @method static \NorthBees\CapApi\Resources\VehiclesResource vehicles()
 * @method static \NorthBees\CapApi\Resources\NvdResource nvd()
 * @method static \NorthBees\CapApi\Resources\UsedValuesResource usedValues()
 * @method static \NorthBees\CapApi\Resources\UsedValuesLiveResource usedValuesLive()
 * @method static \NorthBees\CapApi\Resources\FutureValuesResource futureValues()
 * @method static \NorthBees\CapApi\Resources\VrmResource vrm()
 * @method static \NorthBees\CapApi\Images\ImageUrlBuilder images()
 *
 * @see \NorthBees\CapApi\Cap
 */
class Cap extends Facade
{
    /**
     * Fake CAP responses. Keys are "service.Operation" (e.g. "nvd.GetTechnicalData")
     * or "service.*"; values are response XML strings or closures returning one.
     *
     * @param  array<string, string|\Closure(array<string, string>): string>  $responses
     */
    public static function fake(array $responses = []): CapFake
    {
        return CapFake::fake($responses);
    }

    protected static function getFacadeAccessor(): string
    {
        return \NorthBees\CapApi\Cap::class;
    }
}
