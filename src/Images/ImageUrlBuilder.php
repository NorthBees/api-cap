<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Images;

use Carbon\CarbonInterface;
use NorthBees\CapApi\CapCredentials;
use NorthBees\CapApi\Enums\CapDatabase;

/**
 * Builds signed CAP VehicleImage URLs.
 *
 * The HASHCODE is an unsalted MD5 over the subscriber ID, password, database and
 * CAP ID. Every input but the password is visible in the URL, so these URLs must
 * be fetched server-side and never exposed to end users.
 */
final readonly class ImageUrlBuilder
{
    public function __construct(
        private CapCredentials $credentials,
        private string $baseUrl,
    ) {}

    /**
     * @param  int|null  $viewpoint  CAP viewpoint number; ignored for LCV, which has no viewpoints
     */
    public function url(int $capId, CapDatabase $database = CapDatabase::Car, int $width = 800, int $height = 600, ?int $viewpoint = null, ?CarbonInterface $date = null, ?string $imageText = null): string
    {
        return $this->baseUrl.'?'.http_build_query([
            'SUBID' => $this->credentials->subscriberId,
            'HASHCODE' => $this->hash($capId, $database),
            'DB' => $database->value,
            'CAPID' => $capId,
            'DATE' => $date?->format('Y/m/d') ?? '',
            'HEIGHT' => $height,
            'WIDTH' => $width,
            'IMAGETEXT' => $imageText ?? '',
            'VIEWPOINT' => $database === CapDatabase::Lcv ? '' : ($viewpoint ?? ''),
        ], encoding_type: PHP_QUERY_RFC3986);
    }

    private function hash(int $capId, CapDatabase $database): string
    {
        return strtoupper(md5($this->credentials->subscriberId.$this->credentials->password.$database->value.$capId));
    }
}
