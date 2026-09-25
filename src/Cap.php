<?php

declare(strict_types=1);

namespace NorthBees\CapApi;

use NorthBees\CapApi\Enums\CapDatabase;
use NorthBees\CapApi\Exceptions\CapMissingCredentialsException;
use NorthBees\CapApi\Images\ImageUrlBuilder;
use NorthBees\CapApi\Resources\DvlaResource;
use NorthBees\CapApi\Resources\FutureValuesResource;
use NorthBees\CapApi\Resources\NvdResource;
use NorthBees\CapApi\Resources\UsedValuesLiveResource;
use NorthBees\CapApi\Resources\UsedValuesResource;
use NorthBees\CapApi\Resources\VehiclesResource;
use NorthBees\CapApi\Resources\VrmResource;
use NorthBees\CapApi\Soap\SoapTransport;

/**
 * Entry point for the CAP web services. Immutable: withCredentials() and
 * withDatabase() return new instances, so one tenant's settings never leak into another's.
 */
final readonly class Cap
{
    /**
     * @param  array<string, mixed>  $config  the resolved `cap` config
     */
    public function __construct(
        private SoapTransport $transport,
        private array $config,
        private ?CapCredentials $credentials = null,
        private ?CapDatabase $database = null,
    ) {}

    public function withCredentials(CapCredentials $credentials): self
    {
        return new self($this->transport, $this->config, $credentials, $this->database);
    }

    public function withDatabase(CapDatabase $database): self
    {
        return new self($this->transport, $this->config, $this->credentials, $database);
    }

    /**
     * Instance credentials, falling back to config.
     *
     * @throws CapMissingCredentialsException
     */
    public function credentials(): CapCredentials
    {
        return $this->credentials
            ?? CapCredentials::fromConfig($this->config)
            ?? throw new CapMissingCredentialsException('CAP credentials have not been configured.');
    }

    public function hasCredentials(): bool
    {
        return ($this->credentials ?? CapCredentials::fromConfig($this->config)) !== null;
    }

    public function database(): CapDatabase
    {
        return $this->database
            ?? CapDatabase::tryFrom(strtoupper((string) ($this->config['default_database'] ?? 'CAR')))
            ?? CapDatabase::Car;
    }

    public function dvla(): DvlaResource
    {
        return new DvlaResource($this, $this->transport);
    }

    public function vehicles(): VehiclesResource
    {
        return new VehiclesResource($this, $this->transport);
    }

    public function nvd(): NvdResource
    {
        return new NvdResource($this, $this->transport);
    }

    public function usedValues(): UsedValuesResource
    {
        return new UsedValuesResource($this, $this->transport);
    }

    public function usedValuesLive(): UsedValuesLiveResource
    {
        return new UsedValuesLiveResource($this, $this->transport);
    }

    public function futureValues(): FutureValuesResource
    {
        return new FutureValuesResource($this, $this->transport);
    }

    public function vrm(): VrmResource
    {
        return new VrmResource($this, $this->transport);
    }

    public function images(): ImageUrlBuilder
    {
        return new ImageUrlBuilder(
            $this->credentials(),
            (string) ($this->config['image_url'] ?? 'https://soap.cap.co.uk/images/VehicleImage.aspx'),
        );
    }
}
