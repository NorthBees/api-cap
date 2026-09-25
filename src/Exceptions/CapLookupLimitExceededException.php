<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Exceptions;

/**
 * The subscriber's monthly DVLA lookup allowance has been used up.
 */
class CapLookupLimitExceededException extends CapRequestFailedException {}
