<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Exceptions;

/**
 * Transport failure (connection error or 5xx after retries).
 */
class CapConnectionException extends CapException {}
