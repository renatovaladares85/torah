# Implementation Prompt

Implement only the Torah GLPI plugin. Never modify GLPI core or third-party
plugins. Use English for original code, documentation, comments, UI strings,
and gettext `msgid` values. Preserve native GLPI ACL decisions and implement
restrictions in backend hooks. Before editing, report understanding, minimal
plan, risks, and validation, then wait for explicit approval. Version every
approved delivery and update the changelog and traceability matrix. Built-in
policy rules currently target GLPI tickets only unless another GLPI object is
explicitly approved and documented. Use the official `pt_BR` terminology of the
supported GLPI version for native GLPI concepts; reserve Torah wording for exclusive
concepts or compositions without a direct native equivalent.

The administrative Ticket control catalog represents capabilities supported by the
target GLPI version, not available data or the current DOM. An Opening or Update UI
restriction covers every user-initiated mutation path for its logical control;
Backend remains the explicit extension to non-interactive operations.
