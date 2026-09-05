# Security Policy

## Supported Versions

Security fixes are provided for the latest supported release of this package.

Before the first stable release, security fixes apply to the current development version.

## Reporting a Vulnerability

Please do not report security vulnerabilities through public GitHub issues.

If you discover a security issue, report it privately to the package maintainer.

When reporting a vulnerability, please include:

- a clear description of the issue
- steps to reproduce the problem
- the affected package version or commit
- the potential security impact
- any suggested mitigation, if available

Please allow reasonable time for the issue to be reviewed and addressed before publicly disclosing it.

## Security Scope

Correlation IDs are intended for request tracing and debugging.

They must not be treated as:

- authentication credentials
- authorization identifiers
- API keys
- secrets
- session identifiers

Incoming correlation IDs are considered untrusted input and are validated according to the package configuration.
