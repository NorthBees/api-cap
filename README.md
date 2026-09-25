# NorthBees CAP API

[![Tests](https://github.com/northbees/cap-api/actions/workflows/tests.yml/badge.svg)](https://github.com/northbees/cap-api/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-777bb4.svg?style=flat-square)](composer.json)

A Laravel SDK for the [CAP HPI web services](https://developer.cap.co.uk/webservices): DVLA lookups, vehicle taxonomy, new vehicle data (standard equipment, technical data, options, P11D), used, live and future valuations, and vehicle images.

CAP's services are ASMX SOAP endpoints. This package sends SOAP 1.1 envelopes through Laravel's HTTP client, so `Http::fake()` works in tests. It parses the .NET DataSet responses into typed, readonly DTOs.

## Requirements

- PHP 8.4+ with `ext-dom` and `ext-libxml`
- Laravel 12 or 13

## Installation

```bash
composer require northbees/cap-api
```

The service provider and the `Cap` facade are auto-discovered.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=cap.config
```

## Configuration

```env
CAP_SUBSCRIBER_ID=123456
CAP_PASSWORD=your-password
CAP_DEFAULT_DATABASE=CAR   # CAR or LCV

# Optional
CAP_BASE_URL=https://soap.cap.co.uk
CAP_TIMEOUT=15
CAP_CONNECT_TIMEOUT=5
CAP_RETRY_TIMES=2
CAP_RETRY_SLEEP_MS=250
CAP_LOG=false
CAP_LOG_CHANNEL=
```

The `.env` credentials are only a fallback. Multi-tenant apps should pass credentials per call (see below).

## Usage

Resolve the client from the container or use the facade. Each CAP service is a resource on the client:

```php
use NorthBees\CapApi\Cap;

$cap = app(Cap::class);

$cap->dvla();           // DVLA / SMMT / CAP identification by VRM or VIN
$cap->vehicles();       // Taxonomy and CAP ID <-> CAP code
$cap->nvd();            // Standard equipment, technical data, options, P11D
$cap->usedValues();     // Monthly Black Book used values
$cap->usedValuesLive(); // CAP Live daily values
$cap->futureValues();   // Residual values
$cap->vrm();            // One-call VRM lookup + valuation
$cap->images();         // Signed image URLs
```

### Per-tenant credentials

`Cap` is immutable. `withCredentials()` and `withDatabase()` return new instances, so one tenant's settings never leak into another's:

```php
use NorthBees\CapApi\CapCredentials;
use NorthBees\CapApi\Enums\CapDatabase;

$cap = app(Cap::class)
    ->withCredentials(new CapCredentials($subscriberId, $password))
    ->withDatabase(CapDatabase::Lcv);
```

The client is bound as a scoped singleton, so queue workers and Octane get a fresh instance per job or request. `CapCredentials` redacts the password from `var_dump()` and `print_r()`.

### DVLA lookup

```php
$result = $cap->dvla()->lookupVrm('AB12 CDE'); // or ->lookupVin($vin)

$result->cap->capId;          // 56520
$result->cap->database;       // CapDatabase::Car (detected from the vehicle type)
$result->cap->derivative;     // "2.0T ST-3 5dr"
$result->cap->introduced;     // CarbonImmutable
$result->cap->transmission;   // "Manual" (falls back to the CAP code)
$result->dvla->vin;
$result->dvla->firstRegistrationDate;
$result->alternativeDerivatives; // list<AlternativeDerivative>
```

Each lookup counts against the subscriber's monthly allowance, so DVLA and VRM calls are never retried. Lookups throw:

- `CapLookupLimitExceededException` when the monthly allowance has been used up.
- `CapNoMatchException` when neither the DVLA nor CAP could identify the vehicle.
- `CapRequestFailedException` for any other failure reported by CAP.

### Taxonomy

The CAP hierarchy is manufacturer → range → model → derivative (CAP ID).

```php
$manufacturers = $cap->vehicles()->manufacturers();               // Collection<Manufacturer>
$ranges = $cap->vehicles()->ranges($manufacturer->code);          // Collection<Range>
$models = $cap->vehicles()->models($range->code);                 // Collection<Model>
$derivatives = $cap->vehicles()->derivatives($model->code);       // Collection<Derivative>
$derivatives = $cap->vehicles()->derivativesForRange($range->code, includeOnRunout: true);

$cap->vehicles()->description($capId);   // ?CapDescription
$cap->vehicles()->capCodeFor($capId);    // ?string
$cap->vehicles()->capIdFor($capCode);    // ?int
```

Every taxonomy method accepts `justCurrent`, `includeOnRunout` (which switches to the `_IncludeOnRunout` operation) and an optional `CapDatabase`.

### New vehicle data

```php
$equipment = $cap->nvd()->standardEquipment($capId, $specDate);  // Collection<StandardEquipmentItem>
$technical = $cap->nvd()->technicalData($capId, $specDate);      // TechnicalData

$technical->value(TechnicalData::CO2);   // "169"
$technical->byCategory();                // ['Emissions' => [TechnicalDataItem, ...], ...]

$cap->nvd()->bulkTechnicalData([$capId1, $capId2], $specDate, 'CO2, SEATS');
$cap->nvd()->optionsBundle($capId);      // OptionBundle (raw tables)
$cap->nvd()->p11d($capId, 2025, 2026, 2027);
```

CAP only returns technical data and equipment for dates on or after the model's introduction. For older vehicles, pass `max(first registration, $result->cap->introduced)`, or pass no date with `justCurrent: true`.

### Valuations

```php
// CAP Live (daily)
$live = $cap->usedValuesLive()->valuation($capId, $registeredAt, $mileage);
$live->values->retail; // also clean, average, below

// Monthly Black Book
$used = $cap->usedValues()->valuation($capId, $registeredAt, $mileage);
$used = $cap->usedValues()->valuationForYearMonth($capId, 2019, 3, $mileage);

// Residual value
$future = $cap->futureValues()->valuation($capId, $registeredAt, monthsToValuation: 36, mileage: 30000);

// Lookup + valuation in one chargeable call
$vrm = $cap->vrm()->valuationByVrm('AB12CDE', $mileage, withStandardEquipment: true);
$vrm->lookup->capId;
$vrm->valuation->retail;
```

Values are whole pounds. When CAP reports a failure (for example, "Mileage out of bounds"), a `CapRequestFailedException` is thrown carrying CAP's message.

### Images

```php
$url = $cap->images()->url($capId, CapDatabase::Car, width: 1280, height: 720, viewpoint: 3);
```

> [!WARNING]
> CAP signs image URLs with an unsalted MD5 of the subscriber ID, password, database and CAP ID. Every input except the password appears in the URL, so a leaked URL allows an offline attack on the password. Fetch images server-side and serve your own copy. Never put these URLs in pages sent to browsers.

## Errors

Every exception extends `NorthBees\CapApi\Exceptions\CapException`, which carries `$service` and `$operation`. Messages never include credentials.

| Exception | Raised when |
|---|---|
| `CapConnectionException` | Connection failure, or a 5xx without a SOAP fault, after retries |
| `CapSoapFaultException` | The service returned a `soap:Fault` (HTTP 500 is never retried) |
| `CapRequestFailedException` | CAP answered with `Success=false` |
| `CapLookupLimitExceededException` | The monthly DVLA allowance has been used up |
| `CapNoMatchException` | A lookup or valuation found nothing |
| `CapInvalidResponseException` | The response was not valid XML, contained a DTD, or had no result element |
| `CapMissingCredentialsException` | No credentials were passed and none are configured |

Only connection errors and 502, 503 and 504 responses are retried.

## Testing your application

`Cap::fake()` routes requests by endpoint and `SOAPAction`. Keys are `service.Operation`, `service.*` or `*`. Operations you haven't faked return a SOAP fault, so they fail loudly.

```php
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Facades\Cap;
use NorthBees\CapApi\Testing\CapResponse;

$fake = Cap::fake([
    'dvla.DVLALookupVRM' => CapResponse::dvla(
        dvla: ['VRM' => 'AB12CDE', 'FIRSTREG_DATE' => '20190301'],
        cap: ['CAPID' => 56520, 'VEHICLETYPE' => 'Car', 'MANUFACTURER' => 'FORD'],
    ),
    'vehicles.GetCapMan' => CapResponse::dataSet(CapService::Vehicles, 'GetCapMan', [
        'Table' => [['CMan_Code' => 2514, 'CMan_Name' => 'FORD']],
    ]),
    'usedvalues.*' => CapResponse::failure(CapService::UsedValues, 'GetUsedValuation', 'Mileage out of bounds'),
    'nvd.GetTechnicalData' => fn (array $params) => CapResponse::dataSet(/* ... */),
]);

// ... exercise your code ...

$fake->assertCalled('dvla.DVLALookupVRM', fn (array $params) => $params['vrm'] === 'AB12CDE');
$fake->assertNotCalled('vrm.VRMValuation');
```

Recorded parameters never include the password. `CapResponse` also provides `result()` for typed (non-DataSet) results, `fault()`, `envelope()` and `fixture()`.

## Development

```bash
composer test     # Pest
composer lint     # Pint
composer analyse  # Larastan
```

Each resource method documents its WSDL element names, order and types. .NET silently ignores elements that are mis-cased or out of order and uses default values instead, so the feature tests assert the exact request body for every operation.

The fixtures in `tests/fixtures` were reconstructed from historical CAP responses. To check the SDK against the real services, run the live suite. It is excluded from CI and never spends a DVLA lookup unless `CAP_LIVE_VRM` is set.

```bash
CAP_LIVE=1 CAP_SUBSCRIBER_ID=... CAP_PASSWORD=... vendor/bin/pest --group=live
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
