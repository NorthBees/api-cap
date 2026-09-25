<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Resources;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use NorthBees\CapApi\Cap;
use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Soap\SoapResult;
use NorthBees\CapApi\Soap\SoapTransport;

/**
 * One CAP ASMX service. Subclasses document each operation's WSDL element order.
 */
abstract class Resource
{
    public function __construct(
        protected readonly Cap $cap,
        protected readonly SoapTransport $transport,
    ) {}

    abstract protected function service(): CapService;

    /**
     * Call an operation with credentials prepended (they are first in every CAP WSDL sequence).
     *
     * @param  array<string, bool|int|float|string|DateTimeInterface|null>  $params  in WSDL order, excluding credentials
     */
    protected function call(string $operation, array $params, bool $retry = true, bool $assertSuccess = true): SoapResult
    {
        $credentials = $this->cap->credentials();
        [$subscriberElement, $passwordElement] = $this->service()->credentialElements();

        $result = $this->transport->call(
            $this->service(),
            $operation,
            [$subscriberElement => $credentials->subscriberId, $passwordElement => $credentials->password] + $params,
            $retry,
        );

        return $assertSuccess ? $result->assertSuccess() : $result;
    }

    protected function database(?CapDatabase $database): string
    {
        return ($database ?? $this->cap->database())->value;
    }

    protected function date(?CarbonInterface $date): CarbonImmutable
    {
        return CarbonImmutable::instance($date ?? CarbonImmutable::now())->startOfDay();
    }
}
