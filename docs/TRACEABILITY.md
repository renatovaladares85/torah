# Requirement Traceability

| Requirement | Implementation | Validation |
|---|---|---|
| Restrictive-only authorization | `PolicyResolver`, `TicketMutationGuard` | Policy and guard tests |
| Profile/entity isolation | `AuthorizationContext`, repository scope queries | Resolver tests |
| Exact then nearest recursive policy | `PolicyResolver` | Ancestor precedence tests |
| No default blocking | Empty rule persistence | Installation and save tests |
| Backend actor enforcement | `ActorPayloadInspector`, ticket and relation hooks | Actor payload unit tests |
| Backend property enforcement | `FieldMutationDetector`, ticket update hook | Property catalog tests |
| External capability support | `CapabilityRegistry`, `PolicyApi` | Capability tests |
| Plugin-owned persistence | `DatabaseInstaller`, `GlpiPolicyStore` | GLPI lifecycle matrix |
| Secure administration | Admin endpoints and use cases | ACL/CSRF tests |
| Safe audit data | `AuditLogger` | Audit payload tests |
| English source, pt_BR translation | `locales/torah.pot`, `pt_BR.po`, `pt_BR.mo` | Locale and `msgfmt` checks |
| Public repository privacy | Scanner and packaging workflow | CI privacy job |
| No core modifications | Repository scope and CI checks | Package manifest review |

Update this matrix whenever a requirement or implementation boundary changes.
