# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project follows Semantic Versioning.

## [Unreleased]

### Added

- Correlation ID generation for incoming HTTP requests
- Support for trusted incoming correlation IDs
- Validation for incoming correlation ID values
- Configurable correlation ID header
- Configurable request attribute name
- Correlation ID response headers
- Correlation ID preservation on rendered exception responses
- Laravel / Monolog log enrichment
- Automatic correlation ID propagation through Laravel's HTTP client
- Automatic correlation ID propagation through queued jobs
- Queue worker correlation context restoration
- Queue context cleanup after successful and failed jobs
- Configurable correlation ID generator
- Laravel facade for accessing the current correlation ID
- Optional W3C `traceparent` interoperability
- Validation and parsing of incoming W3C `traceparent` headers
- W3C trace ID tracking separately from ordinary correlation IDs
- W3C trace flag preservation
- W3C trace context propagation through queued jobs
- W3C `traceparent` propagation through outgoing Laravel HTTP requests
- Generation of a new parent ID for outgoing W3C trace hops
- Configurable queue keys for correlation IDs, trace IDs, and trace flags
- PHPUnit and Orchestra Testbench test coverage
- End-to-end W3C request → queue → worker → HTTP propagation coverage
- Laravel 11 and Laravel 12 automated compatibility testing
- GitHub Actions continuous integration
- Laravel Pint code-style checks

### Changed

- Expanded runtime Illuminate dependencies to explicitly declare package requirements
- Improved request lifecycle handling for long-running Laravel processes
- Improved queue lifecycle handling to prevent context leaking between jobs

### Security

- Incoming correlation IDs are validated before being trusted
- Correlation and W3C trace state are cleared between request and queue execution boundaries