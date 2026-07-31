# Changelog

All notable changes to this project are documented in this file. The format is
based on Keep a Changelog and the project follows Semantic Versioning.

## [0.2.1] - 2026-07-31

### Changed

- Reworked the real policy administration page into a compact GLPI-style
  assistance ticket matrix with opening, update, and visual-only backend
  controls.
- Preserved known stored rules outside the reduced matrix when editing a
  policy, preventing configuration loss during this interface-only delivery.

## [0.2.0] - 2026-06-18

### Added

- Grouped policy administration around assistance ticket rules.
- Add and update blocking for editable ticket properties.
- Per-profile and per-entity actor list options for users, groups, and
  suppliers.
- Backend ticket creation guards and policy option persistence.

## [0.1.1] - 2026-06-15

### Fixed

- Aligned the database uniqueness constraint with the effective policy model so
  each profile and entity can have only one policy set.

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

[0.2.1]: https://github.com/renatovaladares85/torah/releases/tag/0.2.1
[0.2.0]: https://github.com/renatovaladares85/torah/releases/tag/0.2.0
[0.1.1]: https://github.com/renatovaladares85/torah/releases/tag/0.1.1
[0.1.0]: https://github.com/renatovaladares85/torah/releases/tag/0.1.0
