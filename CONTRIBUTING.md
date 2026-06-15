# Contributing

Contributions must preserve Torah's restrictive-only security model and must
not modify GLPI core or third-party plugins.

## Requirements

- Use English for source code, comments, documentation, and translatable source
  strings.
- Add or update tests with every behavior change.
- Update `CHANGELOG.md`, version metadata, and `docs/TRACEABILITY.md` for every
  approved delivery.
- Use synthetic data only.
- Run `composer validate` before submitting a change.
- Do not include generated caches, development dependencies, local settings,
  logs, dumps, credentials, or real GLPI data.

## Commit Scope

Keep commits focused and explain behavior, security impact, and validation.
Refactors must preserve behavior and include regression coverage.

## Pull Requests

Describe the requirement, implementation, tests, privacy review, and residual
risks. Security-sensitive reports must follow `SECURITY.md`, not public issues.

