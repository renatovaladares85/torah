# Torah Repository Instructions

These instructions apply to the entire repository.

## Product Identity

- Public name: **Torah**.
- Technical directory and plugin key: `torah`.
- PHP namespace: `GlpiPlugin\Torah`.
- Gettext domain: `torah`.
- Any spelling other than the public name above is invalid.

## Language

- English is the original and authoritative language for code, comments,
  documentation, UI strings, identifiers, and gettext `msgid` values.
- Portuguese is provided only through the `pt_BR` translation catalog.
- When user-facing plugin documentation or catalog metadata is created or
  materially revised, provide an equivalent `pt_BR` text while keeping English
  authoritative. Keep runtime translations in the gettext catalog.
- Keep `README.md` limited to user-facing Torah information. Development,
  build, CI, and release-maintenance instructions belong outside the README.

## Change Control

- Before modifying files or running impactful commands, summarize the request,
  propose the smallest safe plan, identify risks, describe validation, and wait
  for explicit user approval.
- Never modify GLPI core or third-party plugins. Read-only inspection and test
  fixtures are allowed.
- Refactoring must preserve observable behavior and must be covered by tests.
- Do not change unrelated areas or architecture without explicit approval.
- Record every approved delivery in `CHANGELOG.md` and
  `docs/TRACEABILITY.md`.
- Add every newly identified project rule to this file and to the relevant
  prompt under `docs/prompts/` before implementing work governed by that rule.

## Versioning

- Follow Semantic Versioning and GLPI plugin metadata conventions.
- Every approved delivery changes the plugin version, including documentation,
  security, and compatible refactoring deliveries.
- Keep the version consistent in `setup.php`, `plugin.xml`, release artifacts,
  documentation, and changelog entries.

## Release Automation

- Build production assets only through the versioned explicit allowlist and
  package validator.
- Release tags use the `v<SemVer>` format and must match the declared plugin
  version exactly.
- GitHub Actions may create release drafts only. A human must review assets and
  publish the release explicitly.
- Tags with a SemVer prerelease suffix create drafts marked as prereleases;
  final-version tags create drafts without the prerelease marker.

## Security And Privacy

- This is a public repository. Never commit real tickets, personal data,
  organization names, credentials, tokens, private URLs, internal hostnames,
  database dumps, logs, attachments, screenshots with real data, or local
  filesystem paths.
- Use synthetic identities, `example.test`, RFC-reserved IP ranges, and dummy
  identifiers in tests and documentation.
- Never log ticket content, actor names, e-mail addresses, field values, or
  previous/new values.
- Native GLPI ACL decisions always take precedence. Torah can only add
  restrictions; it can never grant access.
- JavaScript is presentation only. Enforcement must exist in backend hooks.
- Validate HTTP method, CSRF token, ACLs, entity access, profile, identifiers,
  and catalog keys at every write endpoint.
- Use GLPI Query Builder for data operations. Fixed migration DDL is allowed;
  user-controlled SQL concatenation is prohibited.
- Run `tools/check-public-repo.sh` before commit, packaging, or remote push.
- A remote push requires a separate explicit user approval after reporting
  files, version, tests, results, privacy checks, and residual risks.

## Architecture

- Keep controllers, endpoints, and hooks thin. They validate/adapt input and
  call application services.
- Keep policy resolution and mutation decisions in domain/application classes.
- Use DTOs and interfaces only for concrete boundaries.
- Store plugin data only in plugin-owned tables and use idempotent GLPI
  migrations.
- Built-in policy rules currently target GLPI tickets only. Expanding built-in
  rules to other GLPI objects requires explicit approval and documentation.
- The administrative Ticket control catalog represents capabilities supported by
  the target GLPI version; it must not depend on available data or the current DOM.
- An Opening or Update UI restriction covers every user-initiated mutation path
  for that logical control. Backend remains the explicit extension to
  non-interactive operations.
