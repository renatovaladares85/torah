# Torah

Torah is a community plugin for GLPI 10 that adds configurable, complementary
restrictions to ticket fields and actions.

The plugin never grants permissions. Native GLPI authorization remains the
first and authoritative decision. Torah only rejects a mutation when GLPI
allows it and an applicable Torah policy marks the corresponding rule as
blocked.

## Compatibility

| Torah | GLPI | PHP |
|---|---|---|
| 0.3.x | >= 10.0.10 and < 10.0.99 | >= 8.2 |
| 0.1.x | >= 10.0.10 and < 10.0.99 | >= 8.2 |

The initial validation targets GLPI 10.0.10 and 10.0.25. Compatibility outside
this matrix is not declared.

## Main Features

- Policy sets scoped by active GLPI profile and ticket entity.
- Exact-entity policy precedence, followed by the nearest recursive ancestor.
- Ticket rules grouped under assistance settings.
- Opening and Update interface restrictions for 19 ticket controls.
- Per-actor control over user, group, and supplier lists.
- Optional Backend enforcement for selected opening/update restrictions.
- Optional external capabilities without mandatory plugin dependencies.
- Safe audit logging without ticket content or personal data.
- English source strings and a complete Brazilian Portuguese translation.

Opening and Update make a selected control read-only in the relevant GLPI
ticket form. Backend makes the same selected action restrictive in server-side
hooks, including API and automatic processes. JavaScript is presentation only;
GLPI ACLs remain authoritative and Torah never grants an access right.

Policies saved before 0.3.0 keep their former backend enforcement until saved
again. New policies start with no restrictions or Backend selections.

## Installation

1. Place the release directory at `<GLPI_ROOT>/plugins/torah`.
2. Open **Setup > Plugins**.
3. Install and activate **Torah**.
4. Open the plugin configuration page and create policy sets.

Do not rename the `torah` directory.

## Configuration

Each policy set selects one profile, one entity, a recursive flag, actor list
options, and the rules to block. Built-in rules currently affect tickets only.
For a ticket, Torah uses exactly one policy set:

1. The policy set for the exact ticket entity.
2. Otherwise, the nearest recursive policy set on an ancestor entity.
3. Otherwise, no interference.

Rules from multiple policy sets are never merged. A policy set with no checked
rules intentionally stops inheritance and applies no property restrictions.
Actor list options default to users, groups, and suppliers when they are not
explicitly configured.

For cron, CLI and other profileless processes, configure **Profile for backend
processes without a user session**. It is used only to find a Torah policy; it
does not give GLPI permissions. Without it, Torah leaves profileless processes
unchanged. Backend enforcement can intentionally affect automatic GLPI work.

## Upgrade And Uninstall

Use the GLPI plugin page to run upgrades. Migrations are idempotent. Disabling
the plugin immediately stops Torah enforcement. Uninstalling removes Torah's
own tables and does not modify GLPI core data.

## Security And Privacy

See [SECURITY.md](SECURITY.md) and [docs/PRIVACY.md](docs/PRIVACY.md). Do not
open public issues containing real GLPI data, credentials, logs, or screenshots
with personal information.

## Development

```bash
composer install
composer validate
composer validate:privacy
composer cs
composer analyse
composer test
composer qa
```

The repository contains plugin code only. GLPI core is an external test
dependency and must never be patched by this project.

Optional integrations are documented in
[docs/EXTERNAL_CAPABILITIES.md](docs/EXTERNAL_CAPABILITIES.md).

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
