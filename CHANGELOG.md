# Changelog

All notable changes to this project are documented in this file. The format is
based on Keep a Changelog and the project follows Semantic Versioning.

## [0.1.0] - 2026-06-15

### Added

- Initial independent Torah plugin for GLPI 10.
- Profile and entity scoped policy sets with recursive inheritance.
- Central policy catalog for ticket actors, properties, and external actions.
- Backend mutation guards for GLPI ticket mutation paths.
- Administrative interface, English source text, and `pt_BR` translation.
- Secure audit events that exclude ticket content and personal data.
- Installation, upgrade, lifecycle, policy, security, and privacy tests.
- Public-repository privacy checks, CI validation, and safe packaging workflow.

### Security

- Native GLPI ACLs remain authoritative and cannot be relaxed by Torah.
- Backend hooks reject manual bypass attempts independently of JavaScript.
- Repository and package validation reject common secrets and private artifacts.

[0.1.0]: https://github.com/renatovaladares85/torah/releases/tag/0.1.0

