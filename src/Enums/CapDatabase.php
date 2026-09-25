<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Enums;

enum CapDatabase: string
{
    case Car = 'CAR';
    case Lcv = 'LCV';

    /**
     * Map the DVLA lookup's CAP vehicle type ("Car", "Light Commercial Vehicle") to a database.
     */
    public static function fromVehicleType(?string $vehicleType): self
    {
        return match (strtolower(trim((string) $vehicleType))) {
            'light commercial vehicle', 'lcv', 'van' => self::Lcv,
            default => self::Car,
        };
    }
}
