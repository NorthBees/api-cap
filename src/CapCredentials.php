<?php

declare(strict_types=1);

namespace NorthBees\CapApi;

/**
 * A CAP subscriber ID and password. The password is redacted from dumps.
 */
final readonly class CapCredentials
{
    public function __construct(
        public int $subscriberId,
        #[\SensitiveParameter] public string $password,
    ) {}

    /**
     * Build credentials from the package config, or null when either value is blank.
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): ?self
    {
        $subscriberId = $config['subscriber_id'] ?? null;
        $password = $config['password'] ?? null;

        if (! is_numeric($subscriberId) || ! is_string($password) || $password === '') {
            return null;
        }

        return new self((int) $subscriberId, $password);
    }

    /**
     * @return array{subscriberId: int, password: string}
     */
    public function __debugInfo(): array
    {
        return ['subscriberId' => $this->subscriberId, 'password' => '********'];
    }
}
