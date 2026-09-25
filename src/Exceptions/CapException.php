<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Exceptions;

use NorthBees\CapApi\Enums\CapService;
use RuntimeException;
use Throwable;

/**
 * Base exception for every error raised by the CAP SDK. Messages never contain credentials.
 */
class CapException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?CapService $service = null,
        public readonly ?string $operation = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
