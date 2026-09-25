<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Soap;

use DateTimeInterface;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapConnectionException;
use NorthBees\CapApi\Exceptions\CapInvalidResponseException;
use NorthBees\CapApi\Exceptions\CapSoapFaultException;
use NorthBees\CapApi\Xml\XmlLoader;
use Throwable;

/**
 * Sends SOAP 1.1 requests through Laravel's HTTP client, so Http::fake() works in tests.
 */
final class SoapTransport
{
    /**
     * @param  array<string, mixed>  $config  the resolved `cap` config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @param  array<string, bool|int|float|string|DateTimeInterface|null>  $params  in WSDL order
     *
     * @throws CapConnectionException|CapSoapFaultException|CapInvalidResponseException
     */
    public function call(CapService $service, string $operation, array $params, bool $retry = true): SoapResult
    {
        $startedAt = microtime(true);
        $response = $this->send($service, $operation, SoapEnvelope::build($service, $operation, $params), $retry);

        $this->log($service, $operation, $response->status(), $startedAt);

        return $this->parse($service, $operation, $response);
    }

    private function send(CapService $service, string $operation, string $envelope, bool $retry): Response
    {
        $attempts = $retry ? max(1, (int) data_get($this->config, 'retry.times', 2) + 1) : 1;

        try {
            return Http::withHeaders(['SOAPAction' => $service->soapAction($operation)])
                ->timeout((int) data_get($this->config, 'timeout', 15))
                ->connectTimeout((int) data_get($this->config, 'connect_timeout', 5))
                ->retry(
                    $attempts,
                    (int) data_get($this->config, 'retry.sleep_ms', 250),
                    fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && in_array($exception->response->status(), [502, 503, 504], true)),
                    throw: false,
                )
                ->withBody($envelope, 'text/xml; charset=utf-8')
                ->post($this->endpoint($service));
        } catch (ConnectionException $exception) {
            throw new CapConnectionException("Unable to connect to CAP {$service->value} service.", $service, $operation, previous: $exception);
        }
    }

    private function parse(CapService $service, string $operation, Response $response): SoapResult
    {
        $body = $response->body();

        if ($response->serverError() && ! str_contains($body, 'Fault')) {
            throw new CapConnectionException("CAP {$service->value} service returned HTTP {$response->status()}.", $service, $operation);
        }

        $document = XmlLoader::load($body);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('soap', SoapEnvelope::SOAP_NAMESPACE);

        $faults = $xpath->query('//soap:Body/soap:Fault');
        $fault = $faults === false ? null : $faults->item(0);

        if ($fault !== null) {
            throw new CapSoapFaultException(
                trim((string) $xpath->evaluate('string(faultcode)', $fault)),
                trim((string) $xpath->evaluate('string(faultstring)', $fault)),
                $service,
                $operation,
            );
        }

        if (! $response->successful()) {
            throw new CapInvalidResponseException("CAP {$operation} returned HTTP {$response->status()}: ".XmlLoader::snippet($body), $service, $operation);
        }

        $results = $xpath->query("//soap:Body/*[local-name()='{$operation}Response']/*[local-name()='{$operation}Result']");
        $result = $results === false ? null : $results->item(0);

        if (! $result instanceof \DOMElement) {
            throw new CapInvalidResponseException("CAP {$operation} response had no {$operation}Result element.", $service, $operation);
        }

        return new SoapResult($result, $service, $operation);
    }

    private function endpoint(CapService $service): string
    {
        return rtrim((string) data_get($this->config, 'base_url', 'https://soap.cap.co.uk'), '/').'/'.$service->path();
    }

    private function log(CapService $service, string $operation, int $status, float $startedAt): void
    {
        if (! data_get($this->config, 'logging.enabled', false)) {
            return;
        }

        Log::channel(data_get($this->config, 'logging.channel'))->info('CAP request', [
            'service' => $service->value,
            'operation' => $operation,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
