<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Exceptions;

/**
 * CAP answered the request but reported Success=false with a failure message.
 */
class CapRequestFailedException extends CapException {}
