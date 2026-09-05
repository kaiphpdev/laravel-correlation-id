# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project follows Semantic Versioning.

## [Unreleased]

### Added

- Correlation ID generation for incoming HTTP requests
- Support for trusted incoming correlation IDs
- Validation for incoming correlation ID values
- Configurable correlation ID header
- Request attribute storage
- Laravel / Monolog log enrichment
- Automatic propagation through Laravel's HTTP client
- Automatic propagation through queued jobs
- Queue worker restoration and cleanup
- Protection against stale correlation IDs in long-running workers
- Configurable correlation ID generator
- Laravel facade for accessing the current correlation ID
- PHPUnit and Orchestra Testbench coverage
- Laravel 11 and Laravel 12 compatibility testing
- GitHub Actions continuous integration
- Laravel Pint code-style checks
