# Torah

Torah is a community plugin for GLPI 10 that adds configurable, complementary
restrictions to ticket fields and actions.

Torah never grants permissions. Native GLPI authorization remains the first
and authoritative decision. Torah only rejects a mutation when GLPI allows it
and an applicable Torah policy blocks the corresponding rule.

Brazilian Portuguese documentation is available in
[README.pt_BR.md](README.pt_BR.md).

## Compatibility

| Torah | GLPI | PHP |
|---|---|---|
| 0.4.x | >= 10.0.20 and < 10.0.99 | >= 8.2 |
| 0.1.x | >= 10.0.20 and < 10.0.99 | >= 8.2 |

The initial validation targets GLPI 10.0.20 and 10.0.25. Compatibility outside
this matrix is not declared.

## Main Features

- Policy sets scoped by active GLPI profile and ticket entity.
- Exact-entity precedence, followed by the nearest recursive ancestor policy.
- Opening and update restrictions for 19 supported ticket controls.
- Global control over user, group, and supplier actor types for each role.
- Optional backend enforcement for selected opening and update restrictions.
- Support for GLPI ticket Select2 controls, Flatpickr date fields, and approval
  request controls.
- Audit events designed not to contain ticket content or personal data.
- English source strings and Brazilian Portuguese runtime translation.

Opening and Update make a selected control read-only in the relevant GLPI
ticket form. Backend makes the same selected action restrictive in server-side
hooks, including API and automatic processes. JavaScript is presentation only;
GLPI ACLs remain authoritative and Torah never grants an access right.

## Installation

1. Download a production `torah-<version>.tar.gz` or `torah-<version>.zip`
   asset from the corresponding published GitHub release.
2. Verify it with the attached `SHA256SUMS.txt` file.
3. Extract it directly into `<GLPI_ROOT>/plugins`. The resulting directory must
   be `<GLPI_ROOT>/plugins/torah`.
4. Open **Setup > Plugins**, then install and activate **Torah**.
5. Open the plugin configuration page and create policy sets.

Do not rename the `torah` directory. Use only the validated production package
assets. GitHub's automatically generated “Source code (zip)” and “Source code
(tar.gz)” archives are repository snapshots, not production packages.

## Configuration

Actor types are global configuration, independent of profiles, entities, and
policies. They determine which new requesters, observers, and assignees can be
added in the interface and backend. Existing actors are never removed
automatically; they remain visible and can be removed while their field is
editable.

Each policy set selects one profile, one entity, a recursive flag, and the
rules to block. Built-in rules affect tickets only. For a ticket, Torah uses
exactly one policy set:

1. The policy set for the exact ticket entity.
2. Otherwise, the nearest recursive policy set on an ancestor entity.
3. Otherwise, no interference.

Rules from multiple policy sets are never merged. A policy set with no checked
rules intentionally stops inheritance and applies no property restrictions.
For GLPI 10.0.20, Torah applies visual restrictions to the native ticket form
identified by `#itil-form`.

## Upgrade And Uninstall

Use the GLPI plugin page to run upgrades. Migrations are idempotent. Disabling
the plugin immediately stops Torah enforcement. Uninstalling removes Torah's
own tables and does not modify GLPI core data.

## Security And Privacy

See [SECURITY.md](SECURITY.md) and [docs/PRIVACY.md](docs/PRIVACY.md). Do not
open public issues containing real GLPI data, credentials, logs, or screenshots
with personal information.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
