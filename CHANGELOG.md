# Changelog

All notable changes to this project are documented in this file. The format is
based on Keep a Changelog and the project follows Semantic Versioning.

## [Unreleased]

## [0.4.12] - 2026-08-20

### Changed

- Aligned Brazilian Portuguese translations of native Ticket concepts with the
  official GLPI 10.0.20 terminology.


## [0.4.11] - 2026-08-20

### Removed

- Removed Torah administration, policy generation, UI locking, translations, and
  persisted policy entries for the native GLPI By (`users_id_recipient`) field.

## [0.4.10] - 2026-08-20

### Fixed

- Completed the static GLPI 10.0.20 Ticket control matrix with title, description,
  entity, location, and contract controls.
- Locked rich-text editing and dynamic SLA/OLA, associated-item, and linked-ticket
  actions while preserving reversible UI policy behavior.
- Applied UI-only policy decisions to explicit interactive SLA/OLA and relation
  mutations without extending frontend-only restrictions to backend operations.


## [0.4.9] - 2026-08-13

### Added

- Added the 40×40 PNG catalog logo and its public raw repository URL to the
  plugin metadata and validated production package.
- Added deterministic release notes generated from the matching changelog
  section and validated production package assets.
- Added regression coverage for release-note extraction and catalog package URL
  validation cases.
- Added a Brazilian Portuguese user-facing README equivalent.

### Changed

- Kept the English README focused on Torah usage, installation, configuration,
  security, compatibility, and official production packages.
- Preserved 0.4.8 as an immutable internal development milestone, without a
  GitHub release, and identified older changelog entries as development
  milestones rather than published releases.
- Prepared 0.4.9 as the first stable public release, with only validated
  production package assets and checksums.

### Fixed

- Prevented draft releases from being created with static notes unrelated to
  the matching changelog section.
- Removed changelog links to releases and tags that do not exist.
- Cleaned up Torah-owned global configuration when uninstalling the plugin.

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

[Unreleased]: https://github.com/renatovaladares85/torah/compare/v0.4.12...HEAD
[0.4.12]: https://github.com/renatovaladares85/torah/compare/v0.4.11...v0.4.12
[0.4.11]: https://github.com/renatovaladares85/torah/compare/v0.4.10...v0.4.11
[0.4.10]: https://github.com/renatovaladares85/torah/compare/v0.4.9...v0.4.10
[0.4.9]: https://github.com/renatovaladares85/torah/blob/v0.4.9/CHANGELOG.md#049---2026-08-13
[0.4.8]: https://github.com/renatovaladares85/torah/blob/v0.4.8/CHANGELOG.md#048---2026-08-01
