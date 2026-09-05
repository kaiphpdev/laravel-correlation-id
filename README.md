# Laravel Correlation ID

A lightweight Laravel package for end-to-end correlation ID tracing across HTTP requests, application logs, outgoing HTTP calls, and queued jobs.

It helps you follow a single request across different parts of your Laravel application and connected services using one consistent correlation ID.

---

## Features

- Automatically generate correlation IDs
- Reuse trusted incoming correlation IDs
- Validate incoming IDs before accepting them
- Configurable correlation ID header
- Store the current ID in Laravel request attributes
- Add correlation IDs to Laravel / Monolog logs
- Automatically propagate IDs through Laravel's HTTP client
- Automatically propagate IDs through queued jobs
- Restore correlation IDs inside queue workers
- Safe cleanup after requests and queued jobs
- Protect against stale IDs in long-running workers
- Configurable UUID / custom ID generator
- Simple Laravel facade for accessing the current ID
- Laravel 11 and Laravel 12 support
- PHP 8.2+

---

## Why Laravel Correlation ID?

Applications often involve more than a single controller request.

A request may:

1. Enter your Laravel API
2. Write multiple log entries
3. Call another service
4. Dispatch a queued job
5. Continue processing inside a queue worker

Without a shared identifier, debugging the entire request flow can be difficult.

Laravel Correlation ID keeps the same identifier available across these operations.

```text
Client Request
    |
    | X-Correlation-ID: abc-123
    v
Laravel Application
    |
    +---- Logs
    |       correlation_id = abc-123
    |
    +---- HTTP Request
    |       X-Correlation-ID: abc-123
    |
    +---- Queued Job
            correlation_id = abc-123
```

---

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

---

## Installation

```bash
composer require kaiphpdev/laravel-correlation-id
```

Laravel package discovery will automatically register the package service provider.

---

## Publish Configuration

```bash
php artisan vendor:publish --tag=correlation-id-config
```

This creates:

```text
config/correlation-id.php
```

---

## Configuration

```php
<?php

return [
    'header' => 'X-Correlation-ID',
    'request_attribute' => 'correlation_id',
    'generator' => \LaravelCorrelationId\Generators\UuidGenerator::class,
    'trust_incoming' => true,
    'incoming' => [
        'max_length' => 128,
        'pattern' => '/^[A-Za-z0-9._:-]+$/',
    ],
    'logging' => [
        'enabled' => true,
        'key' => 'correlation_id',
    ],
    'http_client' => [
        'enabled' => true,
    ],
    'queue' => [
        'enabled' => true,
        'payload_key' => 'correlation_id',
    ],
];
```

---

## Basic Usage

```php
use Illuminate\Support\Facades\Route;

Route::middleware('correlation-id')->group(function () {
    Route::get('/orders', function () {
        return response()->json([
            'message' => 'Orders',
        ]);
    });
});
```

If the incoming request does not contain a correlation ID, the package generates one automatically.

```http
HTTP/1.1 200 OK
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

---

## Incoming Correlation IDs

Clients may send an existing ID:

```http
GET /api/orders

X-Correlation-ID: abc-123
```

When `trust_incoming` is enabled, the package validates and reuses the incoming value.

To always generate your own IDs:

```php
'trust_incoming' => false,
```

---

## Incoming ID Validation

```php
'incoming' => [
    'max_length' => 128,
    'pattern' => '/^[A-Za-z0-9._:-]+$/',
],
```

Invalid incoming IDs are rejected and replaced with a newly generated correlation ID.

---

## Accessing the Current Correlation ID

```php
use LaravelCorrelationId\Facades\CorrelationId;
$id = CorrelationId::get();
if (CorrelationId::has()) {
    $id = CorrelationId::get();
}
```

You may also resolve the manager directly:

```php
use LaravelCorrelationId\CorrelationIdManager;
$manager = app(CorrelationIdManager::class);
$id = $manager->get();
```

---

## Request Attributes

```php
use Illuminate\Http\Request;
public function show(Request $request)
{
    $correlationId = $request->attributes->get('correlation_id');
}
```

The attribute name is configurable through `request_attribute`.

---

## Logging

When an ID is active, the package adds it to Monolog's `extra` data.

```php
use Illuminate\Support\Facades\Log;
Log::info('Order created', [
    'order_id' => 100,
]);
```

The resulting record can contain:

```json
{
    "context": {
        "order_id": 100
    },
    "extra": {
        "correlation_id": "abc-123"
    }
}
```

Disable logging integration:

```php
'logging' => [
    'enabled' => false,
    'key' => 'correlation_id',
],
```

---

## Outgoing HTTP Requests

```php
use Illuminate\Support\Facades\Http;
$response = Http::get('https://example.com/api/orders');
```

The current correlation ID is automatically propagated:

```http
X-Correlation-ID: abc-123
```

Disable propagation:

```php
'http_client' => [
    'enabled' => false,
],
```

---

## Queue Propagation

Correlation IDs are automatically propagated through queued jobs.

```php
SendInvoiceEmail::dispatch();
```

Conceptually, the queue payload receives:

```json
{
    "correlation_id": "abc-123"
}
```

When the worker starts processing the job, the package restores the ID. It also clears correlation state after successful and failed jobs to avoid leaking state between long-running worker executions.

```text
Job starts
    |
    v
Clear previous correlation ID
    |
    v
Restore current job ID
    |
    v
Execute job
    |
    +---- Success ----> Clear ID
    |
    +---- Exception --> Clear ID
```

Disable queue propagation:

```php
'queue' => [
    'enabled' => false,
    'payload_key' => 'correlation_id',
],
```

---

## Custom Correlation ID Generator

The package uses UUIDs by default.

Create a custom generator:

```php
<?php

namespace App\Support;

use LaravelCorrelationId\Contracts\CorrelationIdGenerator;

class CustomCorrelationIdGenerator implements CorrelationIdGenerator
{
    public function generate(): string
    {
        return 'custom-id-' . uniqid();
    }
}
```

Configure it:

```php
'generator' => \App\Support\CustomCorrelationIdGenerator::class,
```

Custom generators are resolved through Laravel's service container.

---

## Changing the Header

The default header is:

```text
X-Correlation-ID
```

Change it with:

```php
'header' => 'X-Request-ID',
```

The configured header is used for incoming and outgoing HTTP requests.

---

## Example Application Flow

```php
Route::middleware('correlation-id')
    ->post('/orders', function () {
        Log::info('Creating order');
        Http::post('https://payment-service.example/pay');
        ProcessOrder::dispatch();
        return response()->json([
            'message' => 'Order created',
        ]);
    });
```

The same ID can then be associated with:

```text
Incoming HTTP request
        |
        v
Laravel application
        |
        +---- Application logs
        |
        +---- Payment API request
        |
        +---- ProcessOrder job
        |
        +---- Queue worker logs
        |
        v
HTTP response
```

---

## Testing

```bash
composer install
composer test
composer format-check
composer format
composer validate
composer check
```

---

## Continuous Integration

The repository uses GitHub Actions to test supported PHP and Laravel combinations.

Current matrix:

```text
PHP 8.2 + Laravel 11
PHP 8.3 + Laravel 11
PHP 8.3 + Laravel 12
PHP 8.4 + Laravel 12
```

CI verifies:

- Composer configuration
- Dependency installation
- Code formatting
- PHPUnit tests

---

## Package Design

```text
CorrelationIdManager
        |
        +---- stores current execution ID

CorrelationIdGenerator
        |
        +---- generates new IDs

CorrelationIdMiddleware
        |
        +---- handles incoming HTTP requests

CorrelationIdProcessor
        |
        +---- enriches application logs

CorrelationIdRequestMiddleware
        |
        +---- propagates IDs through HTTP client

Queue listeners
        |
        +---- propagate and clean IDs in workers
```

---

## Security

Incoming correlation IDs are untrusted external input.

The package supports:

- maximum length validation
- character pattern validation
- disabling incoming ID trust entirely

Correlation IDs are intended for tracing and debugging. They should not be used as authentication tokens, authorization identifiers, secrets, or session IDs.

---

## Contributing

Before submitting a pull request:

```bash
composer check
```

Make sure tests, formatting, and Composer validation pass, and add tests for new behavior.

---

## License

Laravel Correlation ID is open-source software licensed under the MIT License.

See the `LICENSE` file for more information.
