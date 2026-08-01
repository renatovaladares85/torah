# Changelog

All notable changes to this project are documented in this file. The format is
based on Keep a Changelog and the project follows Semantic Versioning.

## [Unreleased]

## [0.4.8] - 2026-08-01

### Added

- Added tag-validated GitHub release automation that creates draft releases
  with production ZIP, TAR.GZ, and SHA-256 checksum assets only.

### Changed

- Built reproducible `.tar.gz` and `.zip` production packages from an explicit
  runtime allowlist, with manifest, security, checksum, and reference validation.
- Updated CI smoke tests to install the extracted production artifact on the
  minimum and latest supported GLPI 10.0.x versions instead of using a symlink
  to the development checkout.

## [0.4.7] - 2026-08-01

### Fixed

- Provisioned ripgrep in both CI runners and updated the pinned checkout action
  to its Node.js 24 runtime.

## [0.4.6] - 2026-08-01

### Fixed

- Restored the Users, Groups, and Suppliers headers in the global actor settings
  matrix and correctly filtered disabled actor types, including namespaced values
  and empty entity groups, through GLPI's native actor hook.

## [0.4.5] - 2026-07-31

### Fixed

- Locked the Approval request validator type and its dynamically loaded user
  and group Select2 controls in the GLPI 10.0.20 ticket form.

## [0.4.4] - 2026-07-31

### Fixed

- Locked the visible GLPI 10.0.20 actor Select2 widgets, their Associate myself
  shortcuts, and the Approval request Select2 while preserving submitted values.
- Relied on the GLPI 10.0.20+ HTTP CSRF pipeline for Torah POST endpoints,
  preventing a second validation from rejecting an already validated token.

### Changed

- Declared GLPI 10.0.20 as the minimum supported GLPI version.

## [0.4.3] - 2026-07-31

### Fixed

- Locked the visible GLPI 10.0.20 Select2 widgets for ticket dropdown controls,
  including dynamically rebuilt fields, while preserving submitted values and
  reversible Flatpickr locks.
- Persisted an explicitly empty Backend selection from the administrative
  matrix so new frontend-only policies are not interpreted as legacy policies.

## [0.4.2] - 2026-07-31

### Added

- Resolution date and Close date as update-only ticket policy controls.

### Fixed

- Locked GLPI 10.0.20 Flatpickr date controls through their visible input,
  calendar controls, and instance configuration while preserving submitted
  values and restoring the original state when a policy changes.
- Added field-specific Torah restriction messages in the interface and backend.

### Changed

- Added lock strategies and friendly labels to the ticket-policy payload.

## [0.4.1] - 2026-07-31

### Fixed

- Targeted the GLPI 10.0.20 Ticket form `#itil-form` when applying Torah UI
  restrictions, instead of a nonexistent `form_ticket` name.
- Removed redundant global actor configuration helper text.

## [0.4.0] - 2026-07-31

### Added

- Global actor itemtype configuration for requesters, observers and assignees.
- Recoverable technical backup and idempotent upgrade migration for legacy
  per-policy actor options.
- Row and column matrix selection helpers with indeterminate state.

### Changed

- Actor itemtype filtering and backend validation now apply without a policy.
- Ticket policy AJAX payloads always include global actor itemtypes.
- Ticket policy JavaScript uses the explicit ticket form selector and reports
  failed policy refreshes without interrupting the form.

### Removed

- Per-policy actor itemtype inputs and redundant Select all/Clear all buttons.

## [0.3.2] - 2026-07-31

### Changed

- Simplified policy administration to entity, profile and recursion scope
  creation, with per-policy select-all and clear-all actions.
- Removed the global backend profile fallback. Processes without an active
  profile are no longer restricted by Torah.

### Removed

- Backend execution context configuration and its persisted profile resolver.
- Matrix search, configured-only filter and redundant administration headings.

## [0.3.1] - 2026-07-31

### Fixed

- Restored the administrative policy page by converting ticket control objects
  into Twig-safe view-model arrays.
- Loaded administrative and ticket policy JavaScript with GLPI-relative script
  paths instead of unsupported `Html::requireJs()` URLs.
- Expanded static analysis and regression coverage for the administrative view
  model and plugin JavaScript registration.
- Removed an unsupported JavaScript hook constant that prevented GLPI 10 from
  loading the plugin before its official update process.

## [0.3.0] - 2026-07-31

### Added

- Explicit Backend policy persistence, with conservative enforcement for
  policy sets created before this release.
- Independent Opening and Update rules for ticket actors and matrix support for
  the 19 supported ticket controls, including composite SLA/OLA controls.
- Hooks for associated items, linked tickets and ticket validations, in
  addition to existing actor, contract and level-agreement paths.
- An optional backend execution profile used only when a process has no active
  user profile.

### Changed

- Opening and Update remain interface restrictions; Backend now controls server
  enforcement for the selected action.
- New policies can be configured completely in their first submission.
- Ticket UI locking is scoped and reversible and no longer disables form values.

## [0.2.2] - 2026-07-31

### Changed

- Grouped existing policy rules by profile in initially collapsed GLPI-style
  accordions, so rules are shown only after expanding their profile.
- Moved visible-field selection to compact column controls in the policy
  matrix and removed the repetitive bulk-action button bar.

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

[0.4.7]: https://github.com/renatovaladares85/torah/releases/tag/0.4.7
[0.4.8]: https://github.com/renatovaladares85/torah/releases/tag/v0.4.8
[0.4.6]: https://github.com/renatovaladares85/torah/releases/tag/0.4.6
[0.4.5]: https://github.com/renatovaladares85/torah/releases/tag/0.4.5
[0.4.4]: https://github.com/renatovaladares85/torah/releases/tag/0.4.4
[0.4.3]: https://github.com/renatovaladares85/torah/releases/tag/0.4.3
[0.4.2]: https://github.com/renatovaladares85/torah/releases/tag/0.4.2
[0.4.1]: https://github.com/renatovaladares85/torah/releases/tag/0.4.1
[0.4.0]: https://github.com/renatovaladares85/torah/releases/tag/0.4.0
[0.3.1]: https://github.com/renatovaladares85/torah/releases/tag/0.3.1
[0.3.2]: https://github.com/renatovaladares85/torah/releases/tag/0.3.2
[0.3.0]: https://github.com/renatovaladares85/torah/releases/tag/0.3.0
[0.2.2]: https://github.com/renatovaladares85/torah/releases/tag/0.2.2
[0.2.1]: https://github.com/renatovaladares85/torah/releases/tag/0.2.1
[0.2.0]: https://github.com/renatovaladares85/torah/releases/tag/0.2.0
[0.1.1]: https://github.com/renatovaladares85/torah/releases/tag/0.1.1
[0.1.0]: https://github.com/renatovaladares85/torah/releases/tag/0.1.0
