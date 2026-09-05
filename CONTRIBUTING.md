# Contributing

Thank you for considering contributing to Laravel Correlation ID.

## Development Setup

Clone the repository and install dependencies:

``` bash
composer install
```

## Quality Checks

Before submitting a pull request, run:

``` bash
composer check
```

This verifies:

-   Composer configuration
-   code formatting
-   automated tests

To run checks individually:

``` bash
composer validate --strict
composer format-check
composer test
```

To automatically format the code:

``` bash
composer format
```

## Tests

New functionality should include appropriate automated tests.

Bug fixes should include a regression test whenever practical.

The package uses PHPUnit and Orchestra Testbench for Laravel integration
testing.

## Pull Requests

Keep pull requests focused on one clear change.

Please:

-   explain what the change does
-   explain why the change is needed
-   add or update tests
-   update documentation when behavior changes
-   make sure `composer check` passes

## Coding Style

The project uses Laravel Pint with the Laravel preset.

Run:

``` bash
composer format
```

before submitting code if formatting changes are needed.

## Commit Messages

Use clear commit messages.

Examples:

``` text
feat: add correlation ID facade
fix: clear correlation ID after queue failure
test: add queue payload coverage
docs: update installation guide
refactor: simplify middleware lifecycle
chore: update development tooling
ci: update compatibility matrix
```

## Backward Compatibility

Avoid breaking public APIs unless the change is planned for a new major
version.

Public behavior includes:

-   configuration keys
-   public classes and contracts
-   middleware aliases
-   facade methods
-   documented package behavior
