<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Exceptions;

use NorthBees\CapApi\Enums\CapService;

/**
 * The service returned a soap:Fault (typically HTTP 500 from ASMX).
 */
class CapSoapFaultException extends CapException
{
    public function __construct(
        public readonly string $faultCode,
        public readonly string $faultString,
        ?CapService $service = null,
        ?string $operation = null,
    ) {
        parent::__construct("CAP SOAP fault [{$faultCode}]: {$faultString}", $service, $operation);
    }
}
