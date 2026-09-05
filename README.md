# Laravel Correlation ID

A lightweight Laravel package for end-to-end correlation ID tracing across HTTP requests, application logs, outgoing HTTP calls, and queued jobs.

It helps you follow a single request across different parts of your Laravel application and connected services using one consistent correlation ID.

The package also provides optional W3C `traceparent` interoperability for propagating trace IDs and trace flags across supported execution boundaries.

---

## Features

- Automatically generate correlation IDs
- Reuse trusted incoming correlation IDs
- Validate incoming IDs before accepting them
- Configurable correlation ID header
- Add the correlation ID to HTTP responses
- Preserve the correlation ID on rendered exception responses
- Store the current ID in Laravel request attributes
- Add correlation IDs to Laravel / Monolog logs
- Automatically propagate IDs through Laravel's HTTP client
- Automatically propagate IDs through queued jobs
- Restore correlation IDs inside queue workers
- Safe cleanup after HTTP requests and queued jobs
- Protection against stale state in long-running workers
- Configurable UUID / custom ID generator
- Simple Laravel facade for accessing the current ID
- Optional W3C `traceparent` support
- W3C trace ID propagation through queued jobs
- W3C trace flag preservation
- W3C `traceparent` propagation to outgoing HTTP requests
- Laravel 11 and Laravel 12 support
- PHP 8.2+

---

## Why Laravel Correlation ID?

Modern applications often involve more than a single controller request.

A request may:

1. Enter your Laravel API
2. Write multiple log entries
3. Call another service
4. Dispatch a queued job
5. Continue processing inside a queue worker
6. Make additional HTTP requests from that worker

Without a shared identifier, debugging the entire request flow can be difficult.

Laravel Correlation ID keeps the same correlation ID available across these operations.

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

When optional W3C support is enabled, an incoming `traceparent` can also provide the trace ID used by the package:

```text
Incoming Request
    |
    | traceparent:
    | 00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01
    v
Laravel Application
    |
    +---- correlation_id
    |       4bf92f3577b34da6a3ce929d0e0e4736
    |
    +---- trace_id
    |       4bf92f3577b34da6a3ce929d0e0e4736
    |
    +---- trace_flags
            01
```

---

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

---

## Installation

Install the package through Composer:

```bash
composer require kaiphpdev/laravel-correlation-id
```

Laravel package discovery automatically registers the package service provider and facade.

---

## Publish Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=correlation-id-config
```

This creates:

```text
config/correlation-id.php
```

---

## Configuration

The default configuration is:

```php
<?php

use LaravelCorrelationId\Generators\UuidGenerator;

return [
    'header' => 'X-Correlation-ID',

    'request_attribute' => 'correlation_id',

    'generator' => UuidGenerator::class,

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
        'trace_payload_key' => 'trace_id',
        'trace_flags_payload_key' => 'trace_flags',
    ],

    'w3c' => [
        'enabled' => false,
        'accept_traceparent' => true,
        'propagate_traceparent' => true,
    ],
];
```

W3C support is disabled by default and must be explicitly enabled.

---

## Basic Usage

Apply the package middleware to routes that should participate in correlation tracing:

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

If the incoming request does not contain a valid correlation ID, the package generates one automatically.

The ID is also added to the HTTP response:

```http
HTTP/1.1 200 OK
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

---

## Incoming Correlation IDs

Clients may provide an existing correlation ID:

```http
GET /api/orders
X-Correlation-ID: abc-123
```

When:

```php
'trust_incoming' => true,
```

the package validates and reuses the incoming value.

To ignore client-provided correlation IDs and always generate your own:

```php
'trust_incoming' => false,
```

---

## Incoming ID Validation

Incoming correlation IDs are treated as untrusted input.

The default validation configuration is:

```php
'incoming' => [
    'max_length' => 128,
    'pattern' => '/^[A-Za-z0-9._:-]+$/',
],
```

If an incoming correlation ID fails validation, the package generates a new ID instead.

This prevents arbitrary or excessively large values from being accepted into logs, queue payloads, and downstream requests.

---

## Accessing the Current Correlation ID

Use the facade:

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

The active correlation ID is stored on the current Laravel request.

```php
use Illuminate\Http\Request;

public function show(Request $request)
{
    $correlationId = $request->attributes->get(
        'correlation_id'
    );
}
```

The attribute name can be changed using:

```php
'request_attribute' => 'correlation_id',
```

---

## Response Headers

The active correlation ID is added to the response using the configured header.

By default:

```http
X-Correlation-ID: abc-123
```

The package also integrates with Laravel's exception response handling so the correlation ID can remain available on rendered exception responses.

This makes it easier to match an error response reported by a client with the corresponding application logs.

---

## Logging

When a correlation ID is active, the package adds it to Monolog's `extra` data.

For example:

```php
use Illuminate\Support\Facades\Log;

Log::info('Order created', [
    'order_id' => 100,
]);
```

The resulting log record can contain:

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

Logging integration can be disabled:

```php
'logging' => [
    'enabled' => false,
    'key' => 'correlation_id',
],
```

The log key is configurable through:

```php
'key' => 'correlation_id',
```

---

## Outgoing HTTP Requests

The package registers global middleware with Laravel's HTTP client.

For example:

```php
use Illuminate\Support\Facades\Http;

$response = Http::get(
    'https://example.com/api/orders'
);
```

When a correlation ID is active, the outgoing request automatically receives:

```http
X-Correlation-ID: abc-123
```

You do not need to manually add the header to every Laravel HTTP client request.

Disable this behavior with:

```php
'http_client' => [
    'enabled' => false,
],
```

---

## Queue Propagation

Correlation IDs are automatically added to Laravel queue payloads.

For example:

```php
SendInvoiceEmail::dispatch();
```

Conceptually, the queue payload receives:

```json
{
    "correlation_id": "abc-123"
}
```

Before the queued job executes, the package restores the correlation ID into the current execution context.

After the job finishes or throws an exception, the context is cleared.

```text
Job starts
    |
    v
Clear stale context
    |
    v
Restore job correlation ID
    |
    v
Execute job
    |
    +---- Success ----> Clear context
    |
    +---- Exception --> Clear context
```

This cleanup is important for long-running Laravel queue workers because the same PHP process may execute many jobs.

Queue propagation can be disabled:

```php
'queue' => [
    'enabled' => false,
    'payload_key' => 'correlation_id',
    'trace_payload_key' => 'trace_id',
    'trace_flags_payload_key' => 'trace_flags',
],
```

---

## W3C Trace Context Interoperability

The package provides optional interoperability with the W3C `traceparent` header.

It is disabled by default:

```php
'w3c' => [
    'enabled' => false,
    'accept_traceparent' => true,
    'propagate_traceparent' => true,
],
```

Enable it with:

```php
'w3c' => [
    'enabled' => true,
    'accept_traceparent' => true,
    'propagate_traceparent' => true,
],
```

This feature is intended to allow correlation tracing to interoperate with services that already exchange W3C trace context.

It does not turn the package into a full distributed tracing or observability platform.

---

## Incoming `traceparent`

With W3C support enabled, the package can accept a valid incoming `traceparent` header.

Example:

```http
traceparent: 00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01
```

The header contains:

```text
00
│
├── Version

4bf92f3577b34da6a3ce929d0e0e4736
│
├── Trace ID

00f067aa0ba902b7
│
├── Parent ID

01
│
└── Trace flags
```

For a valid accepted `traceparent`, the trace ID becomes the active correlation ID:

```text
correlation_id =
4bf92f3577b34da6a3ce929d0e0e4736
```

The package also keeps the trace ID and trace flags as explicit trace context.

---

## Traceparent Precedence

When W3C support and incoming `traceparent` acceptance are enabled, a valid `traceparent` trace ID is used as the active correlation ID.

Conceptually:

```text
Valid traceparent
        |
        v
Use trace ID as correlation ID
```

If no valid accepted trace context is available, normal correlation ID processing continues:

```text
X-Correlation-ID
        |
        v
Validate incoming ID
        |
        +---- Valid ----> Use it
        |
        +---- Invalid --> Generate new ID
```

This keeps ordinary correlation IDs separate from explicit W3C trace context.

---

## W3C Outgoing HTTP Propagation

When all of the following are enabled:

```php
'w3c' => [
    'enabled' => true,
    'propagate_traceparent' => true,
],
```

and the current execution has valid W3C trace context, outgoing Laravel HTTP requests receive a `traceparent` header.

For example:

```http
traceparent: 00-4bf92f3577b34da6a3ce929d0e0e4736-a1b2c3d4e5f60718-01
```

The trace ID remains the same:

```text
4bf92f3577b34da6a3ce929d0e0e4736
```

A new parent ID is generated for the outgoing hop.

This means the package does **not** blindly copy the incoming `traceparent` header.

Conceptually:

```text
Incoming

00-TRACE_ID-PARENT_A-01
          |
          v
Laravel
          |
          v
Outgoing

00-TRACE_ID-PARENT_B-01
```

The trace ID remains associated with the same trace while the outgoing hop receives a new parent identifier.

---

## W3C Queue Propagation

When W3C support is enabled and explicit trace context is active, queue payloads can contain:

```json
{
    "correlation_id": "4bf92f3577b34da6a3ce929d0e0e4736",
    "trace_id": "4bf92f3577b34da6a3ce929d0e0e4736",
    "trace_flags": "01"
}
```

The corresponding keys are configurable:

```php
'queue' => [
    'payload_key' => 'correlation_id',
    'trace_payload_key' => 'trace_id',
    'trace_flags_payload_key' => 'trace_flags',
],
```

When the worker begins processing the job, the package restores the correlation ID and valid trace context.

This allows an outgoing HTTP request made from the queued job to continue using the same trace ID.

```text
Incoming Request
       |
       | trace ID = A
       | flags = 01
       v
Laravel Request
       |
       v
Queue Payload
       |
       | correlation_id = A
       | trace_id = A
       | trace_flags = 01
       v
Queue Worker
       |
       v
Outgoing HTTP
       |
       | trace ID = A
       | new parent ID
       | flags = 01
       v
Downstream Service
```

---

## Trace Flags

The package preserves trace flags associated with accepted W3C trace context.

For example, an incoming:

```http
traceparent: 00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01
```

contains:

```text
trace_flags = 01
```

When that trace context passes through a supported queue boundary and later through an outgoing HTTP request, the trace flags are preserved.

The package validates trace flag formatting before restoring it from a queue payload.

---

## Correlation IDs vs W3C Trace IDs

Correlation IDs and W3C trace IDs are related but not identical concepts.

A normal correlation ID can be:

```text
order-request-123
```

or:

```text
550e8400-e29b-41d4-a716-446655440000
```

A W3C trace ID has a specific 32-character hexadecimal representation:

```text
4bf92f3577b34da6a3ce929d0e0e4736
```

The package tracks explicit W3C trace context separately.

Therefore, an ordinary correlation ID that happens to contain 32 hexadecimal characters is not automatically treated as W3C trace context.

This prevents accidental `traceparent` generation for normal correlation IDs.

---

## Long-Running Worker Safety

Laravel applications may run inside long-lived processes such as queue workers.

Without proper cleanup:

```text
Request / Job A
correlation_id = A

        ↓ process reused

Request / Job B

        ↓

accidentally sees A
```

The package explicitly clears its active state at lifecycle boundaries.

For HTTP requests:

```text
Request starts
    ↓
Clear stale state
    ↓
Set current state
    ↓
Execute request
    ↓
finally
    ↓
Clear state
```

For queue jobs:

```text
Job processing
    ↓
Clear stale state
    ↓
Restore payload state
    ↓
Execute job
    ↓
Processed / Exception
    ↓
Clear state
```

This helps prevent correlation and trace context from leaking between executions.

---

## Custom Correlation ID Generator

UUIDs are used by default:

```php
'generator' => \LaravelCorrelationId\Generators\UuidGenerator::class,
```

You may create your own generator:

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

Then configure it:

```php
'generator' => \App\Support\CustomCorrelationIdGenerator::class,
```

Custom generators are resolved through Laravel's service container and must implement:

```php
LaravelCorrelationId\Contracts\CorrelationIdGenerator
```

---

## Changing the Correlation Header

The default correlation header is:

```text
X-Correlation-ID
```

Change it using:

```php
'header' => 'X-Request-ID',
```

The configured correlation header is used for incoming and outgoing correlation ID propagation.

This setting does not rename the W3C `traceparent` header.

---

## Example Application Flow

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::middleware('correlation-id')
    ->post('/orders', function () {
        Log::info('Creating order');

        Http::post(
            'https://payment-service.example/pay'
        );

        ProcessOrder::dispatch();

        return response()->json([
            'message' => 'Order created',
        ]);
    });
```

The correlation ID can then be associated with:

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
        +---- ProcessOrder queue payload
        |
        +---- Queue worker
        |
        +---- Worker logs
        |
        +---- Worker HTTP requests
        |
        v
HTTP response
```

---

## Failure Behavior

The package is designed so correlation metadata supports debugging without becoming application business logic.

Examples:

- Invalid incoming correlation IDs are not trusted.
- Missing correlation IDs cause a new ID to be generated.
- Invalid W3C trace context is not restored as valid trace state.
- Invalid queue trace flags are ignored.
- Request state is cleared in a `finally` block.
- Queue state is cleared after processed and failed jobs.
- Rendered exception responses can retain the request correlation ID header.

Correlation IDs must not be used as proof of identity or authorization.

---

## Testing

Install dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Check formatting:

```bash
composer format-check
```

Apply formatting:

```bash
composer format
```

Validate Composer configuration:

```bash
composer validate --strict
```

Run the complete package quality gate:

```bash
composer check
```

`composer check` runs Composer validation, formatting checks, and PHPUnit tests.

---

## Continuous Integration

The repository uses GitHub Actions to verify supported PHP and Laravel combinations.

The CI pipeline should verify:

- Composer configuration
- Dependency installation
- Code formatting
- PHPUnit tests

Run the same primary quality gate locally before opening a pull request:

```bash
composer check
```

---

## Package Design

```text
CorrelationIdManager
        |
        +---- current correlation state
        |
        +---- explicit W3C trace state

CorrelationIdGenerator
        |
        +---- generates correlation IDs

CorrelationIdMiddleware
        |
        +---- incoming correlation ID
        |
        +---- incoming traceparent
        |
        +---- response header
        |
        +---- lifecycle cleanup

CorrelationIdProcessor
        |
        +---- Monolog integration

CorrelationIdRequestMiddleware
        |
        +---- outgoing correlation header
        |
        +---- outgoing traceparent

TraceparentParser
        |
        +---- parses incoming trace context

TraceparentGenerator
        |
        +---- creates outgoing traceparent

Queue payload integration
        |
        +---- correlation ID
        |
        +---- trace ID
        |
        +---- trace flags

Queue listeners
        |
        +---- restore job context
        |
        +---- clean worker state
```

---

## Security

Incoming correlation IDs and trace headers originate outside the application and should be considered untrusted input.

The package provides correlation ID controls including:

- maximum length validation
- character pattern validation
- the ability to disable trust of incoming correlation IDs

Correlation and trace identifiers are intended for tracing, observability, and debugging.

They should **not** be used as:

- authentication tokens
- authorization identifiers
- secrets
- session identifiers
- access-control decisions

Do not include sensitive information inside correlation IDs.

---

## Non-Goals

Laravel Correlation ID is intentionally lightweight.

It is not intended to replace:

- OpenTelemetry
- distributed tracing platforms
- application performance monitoring systems
- log aggregation platforms
- metrics systems

Its primary responsibility is reliable correlation-ID propagation with optional W3C `traceparent` interoperability.

---

## Contributing

Before submitting a pull request, run:

```bash
composer check
```

Make sure:

- tests pass
- formatting passes
- Composer validation passes
- new behavior includes appropriate tests

See `CONTRIBUTING.md` for additional guidelines.

---

## Changelog

See `CHANGELOG.md` for release changes.

---

## Security Vulnerabilities

Do not disclose security vulnerabilities publicly through GitHub issues.

See `SECURITY.md` for the responsible disclosure process.

---

## License

Laravel Correlation ID is open-source software licensed under the MIT License.

See the `LICENSE` file for more information.