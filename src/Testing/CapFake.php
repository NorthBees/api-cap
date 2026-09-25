<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Testing;

use Closure;
use DOMElement;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Xml\XmlLoader;
use PHPUnit\Framework\Assert;

/**
 * Fakes the CAP SOAP services via Http::fake(), routing on endpoint path and SOAPAction.
 */
final class CapFake
{
    /**
     * @var list<array{key: string, params: array<string, string>}>
     */
    private array $calls = [];

    /**
     * @param  array<string, string|Closure(array<string, string>): string>  $responses
     */
    private function __construct(private array $responses) {}

    /**
     * @param  array<string, string|Closure(array<string, string>): string>  $responses  keyed "service.Operation", "service.*" or "*"
     */
    public static function fake(array $responses = []): self
    {
        $fake = new self($responses);
        $host = parse_url((string) config('cap.base_url', 'https://soap.cap.co.uk'), PHP_URL_HOST) ?: 'soap.cap.co.uk';

        Http::fake([$host.'/*' => fn (Request $request) => $fake->respond($request)]);

        return $fake;
    }

    /**
     * @param  string|Closure(array<string, string>): string  $response
     */
    public function push(string $key, string|Closure $response): self
    {
        $this->responses[$key] = $response;

        return $this;
    }

    /**
     * @param  (Closure(array<string, string>): bool)|null  $callback
     */
    public function assertCalled(string $key, ?Closure $callback = null): void
    {
        $matching = array_filter($this->calls, fn (array $call): bool => $call['key'] === $key && ($callback === null || $callback($call['params'])));

        Assert::assertNotEmpty($matching, "Expected CAP call [{$key}] was not made.");
    }

    public function assertCalledTimes(string $key, int $times): void
    {
        $count = count(array_filter($this->calls, fn (array $call): bool => $call['key'] === $key));

        Assert::assertSame($times, $count, "Expected CAP call [{$key}] {$times} times, made {$count}.");
    }

    public function assertNotCalled(string $key): void
    {
        $this->assertCalledTimes($key, 0);
    }

    public function assertNothingCalled(): void
    {
        Assert::assertSame([], $this->calls, 'Unexpected CAP calls were made.');
    }

    /**
     * @return list<array{key: string, params: array<string, string>}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    private function respond(Request $request): mixed
    {
        $service = CapService::fromPath((string) parse_url($request->url(), PHP_URL_PATH));
        $operation = basename(trim($request->header('SOAPAction')[0] ?? '', '"'));
        $key = ($service->value ?? 'unknown').'.'.$operation;
        $params = self::params($request->body());

        $this->calls[] = ['key' => $key, 'params' => $params];

        $response = $this->responses[$key]
            ?? $this->responses[($service->value ?? 'unknown').'.*']
            ?? $this->responses['*']
            ?? null;

        if ($response === null) {
            return Http::response(CapResponse::fault("CAP call [{$key}] was not faked."), 500, ['Content-Type' => 'text/xml; charset=utf-8']);
        }

        $body = $response instanceof Closure ? $response($params) : $response;

        return Http::response($body, str_contains($body, 'soap:Fault') ? 500 : 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    }

    /**
     * The operation's parameters (excluding the password) from a request envelope.
     *
     * @return array<string, string>
     */
    private static function params(string $body): array
    {
        $document = XmlLoader::load($body);
        $operation = $document->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body')->item(0)?->firstElementChild;
        $params = [];

        foreach ($operation?->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement && strcasecmp($node->localName, 'password') !== 0) {
                $params[$node->localName] = $node->textContent;
            }
        }

        return $params;
    }
}
