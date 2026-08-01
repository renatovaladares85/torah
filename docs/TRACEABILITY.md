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
| Explicit backend enforcement | `BackendRulePolicy`, `PolicySetInput`, `PolicyResolver::decideBackend` | Backend presence, policy and resolver tests |
| Global actor itemtype configuration | `GlobalActorItemtypePolicy`, `GlpiGlobalActorSettingsStore`, `ActorListFilter` | Global policy, input and actor payload tests |
| Legacy actor option migration | `DatabaseInstaller` global migration and technical Config backup | Migration source and lifecycle validation |
| Backend actor and relation enforcement | `ActorPayloadInspector`, ticket and relation hooks | Actor payload and guard tests |
| Backend property enforcement | `FieldMutationDetector`, ticket update hook | Property catalog tests |
| External capability support | `CapabilityRegistry`, `PolicyApi` | Capability tests |
| Plugin-owned persistence | `DatabaseInstaller`, `GlpiPolicyStore` | GLPI lifecycle matrix |
| Secure administration | GLPI HTTP CSRF pipeline and admin endpoints | Endpoint and form CSRF regression tests |
| Profile-grouped administrative policy matrix UI | `AdminPage`, admin Twig templates, scoped JavaScript | Administrative view-model and matrix action tests |
| Reversible ticket field locks | `TicketControlCatalog`, `TicketPolicyPayload`, Select2/Flatpickr-aware ticket JavaScript scoped to GLPI `#itil-form` | Catalog, payload and JavaScript regression tests |
| GLPI actor and Approval request visual locks | Actor/approval definitions in `TicketControlCatalog` and Select2-aware actor handling in `ticket-policy.js` | Actor selector, Approval request composite and JavaScript regression tests |
| Friendly policy-denial messages | `TicketControlCatalog`, `TicketMutationGuard` | Catalog and guard regression tests |
| Profileless backend behavior | `AuthorizationContextFactory` | Context fallback regression test |
| Safe audit data | `AuditLogger` | Audit payload tests |
| English source, pt_BR translation | `locales/torah.pot`, `pt_BR.po`, `pt_BR.mo` | Locale and `msgfmt` checks |
| Public repository privacy | Scanner and packaging workflow | CI privacy job |
| No core modifications | Repository scope and CI checks | Package manifest review |

Update this matrix whenever a requirement or implementation boundary changes.
