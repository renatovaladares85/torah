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
| Global actor matrix headers and native actor search filtering | Canonical itemtypes in `AdminPolicyModelBuilder`; normalization and recursive pruning in `ActorListFilter` | Administrative model, role combinations, namespaces, grouped results and hook payload regression tests |
| Legacy actor option migration | `DatabaseInstaller` global migration and technical Config backup | Migration source and lifecycle validation |
| Backend actor and relation enforcement | `ActorPayloadInspector`, ticket and relation hooks | Actor payload and guard tests |
| Backend property enforcement | `FieldMutationDetector`, ticket update hook | Property catalog tests |
| External capability support | `CapabilityRegistry`, `PolicyApi` | Capability tests |
| Plugin-owned persistence | `DatabaseInstaller`, `GlpiPolicyStore` | GLPI lifecycle matrix |
| Torah-owned global configuration cleanup | `GlpiGlobalActorSettingsStore::clear` called by `DatabaseInstaller::uninstall` | GLPI lifecycle test verifies the exact `plugin:torah` context is removed while an external context is preserved |
| Secure administration | GLPI HTTP CSRF pipeline and admin endpoints | Endpoint and form CSRF regression tests |
| Profile-grouped administrative policy matrix UI | `AdminPage`, admin Twig templates, scoped JavaScript | Administrative view-model and matrix action tests |
| Reversible ticket field locks | `TicketControlCatalog`, `TicketPolicyPayload`, Select2/Flatpickr-aware ticket JavaScript scoped to GLPI `#itil-form` | Catalog, payload and JavaScript regression tests |
| GLPI actor and Approval request visual locks | Actor definitions, Approval request type/AJAX selectors in `TicketControlCatalog`, and Select2-aware actor handling in `ticket-policy.js` | Actor selector, Approval request composite and JavaScript regression tests |
| Friendly policy-denial messages | `TicketControlCatalog`, `TicketMutationGuard` | Catalog and guard regression tests |
| Profileless backend behavior | `AuthorizationContextFactory` | Context fallback regression test |
| Safe audit data | `AuditLogger` | Audit payload tests |
| English source, pt_BR translation | `locales/torah.pot`, `pt_BR.po`, `pt_BR.mo` | Locale and `msgfmt` checks |
| Public repository privacy | Scanner and packaging workflow | CI privacy job |
| CI validation tool provisioning | `ci.yml` installs ripgrep in each isolated runner | CI quality and GLPI artifact smoke jobs |
| Minimal production distribution | Allowlisted reproducible archives, package validator, and artifact-based GLPI matrix | Packaging regression test, manifest/security checks, and GLPI artifact smoke jobs |
| Human-reviewed production release | Tag/version-validated draft release workflow with ZIP, TAR.GZ, and SHA-256 assets; SemVer prerelease tags are marked as prereleases while final tags remain draft-only | Reusable CI workflow, package validation, checksum verification, and GitHub draft release creation |
| Curated release notes | Deterministic generator extracts the matching changelog section and appends validated package guidance | Release-notes regression tests and draft release workflow |
| User-facing bilingual documentation | English-authoritative README with equivalent Brazilian Portuguese documentation | README review and privacy validation |
| Catalog package integrity | Per-version production package URLs are accepted only after public asset, checksum, tag, and archive-root validation | Catalog metadata regression cases and package validator |
| Catalog visual identity | A 40×40 PNG logo is published through `plugin.xml` using its public raw repository URL and is a required allowlisted production asset | PNG signature and dimensions checks, XML validation, URL/path verification, and package validation |
| No core modifications | Repository scope and CI checks | Package manifest review |

Update this matrix whenever a requirement or implementation boundary changes.
