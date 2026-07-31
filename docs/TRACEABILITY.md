# Requirement Traceability

| Requirement | Implementation | Validation |
|---|---|---|
| Restrictive-only authorization | `PolicyResolver`, `TicketMutationGuard` | Policy and guard tests |
| Profile/entity isolation | `AuthorizationContext`, repository scope queries | Resolver tests |
| Unique policy scope | `DatabaseInstaller`, `GlpiPolicyStore` | GLPI lifecycle matrix |
| Exact then nearest recursive policy | `PolicyResolver` | Ancestor precedence tests |
| No default blocking | Empty rule persistence | Installation and save tests |
| Ticket-only built-in rules | Policy catalog metadata | Catalog tests |
| Opening/update interface controls | `TicketControlCatalog`, `TicketPolicyPresenter` | Catalog and view-model tests |
| Explicit backend enforcement | `BackendRulePolicy`, `PolicyResolver::decideBackend` | Backend policy and resolver tests |
| Actor itemtype options | `ActorItemtypePolicy`, `ActorListFilter`, `GlpiPolicyStore` | Input and actor payload tests |
| Backend actor and relation enforcement | `ActorPayloadInspector`, ticket and relation hooks | Actor payload and guard tests |
| Backend property enforcement | `FieldMutationDetector`, ticket update hook | Property catalog tests |
| External capability support | `CapabilityRegistry`, `PolicyApi` | Capability tests |
| Plugin-owned persistence | `DatabaseInstaller`, `GlpiPolicyStore` | GLPI lifecycle matrix |
| Secure administration | Admin endpoints and use cases | ACL/CSRF tests |
| Profile-grouped administrative policy matrix UI | `AdminPage`, admin Twig templates, scoped JavaScript | Manual GLPI profile accordion and matrix validation |
| Safe audit data | `AuditLogger` | Audit payload tests |
| English source, pt_BR translation | `locales/torah.pot`, `pt_BR.po`, `pt_BR.mo` | Locale and `msgfmt` checks |
| Public repository privacy | Scanner and packaging workflow | CI privacy job |
| No core modifications | Repository scope and CI checks | Package manifest review |

Update this matrix whenever a requirement or implementation boundary changes.
