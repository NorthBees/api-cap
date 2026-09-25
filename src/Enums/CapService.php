<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Enums;

/**
 * The CAP ASMX web services. Each service has its own endpoint, XML namespace
 * and credential element casing, all taken from the published WSDLs.
 */
enum CapService: string
{
    case Dvla = 'dvla';
    case Vehicles = 'vehicles';
    case Nvd = 'nvd';
    case UsedValues = 'usedvalues';
    case UsedValuesLive = 'usedvalueslive';
    case FutureValues = 'futurevalues';
    case Vrm = 'vrm';

    /**
     * The endpoint path relative to the configured base URL.
     */
    public function path(): string
    {
        return $this->value.'/cap'.$this->value.'.asmx';
    }

    /**
     * The WSDL target namespace for request and response elements.
     */
    public function namespace(): string
    {
        return 'https://soap.cap.co.uk/'.$this->value;
    }

    /**
     * The quoted SOAPAction header value for an operation.
     */
    public function soapAction(string $operation): string
    {
        return '"'.$this->namespace().'/'.$operation.'"';
    }

    /**
     * The subscriber and password element names this service expects.
     *
     * @return array{0: string, 1: string}
     */
    public function credentialElements(): array
    {
        return match ($this) {
            self::Dvla, self::Vrm => ['SubscriberID', 'Password'],
            self::UsedValues => ['Subscriber_ID', 'Password'],
            default => ['subscriberId', 'password'],
        };
    }

    /**
     * Resolve a service from an endpoint URL path.
     */
    public static function fromPath(string $path): ?self
    {
        foreach (self::cases() as $service) {
            if (str_ends_with(strtolower($path), $service->path())) {
                return $service;
            }
        }

        return null;
    }
}
