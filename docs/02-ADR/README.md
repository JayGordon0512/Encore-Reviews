# Architecture Decision Records

Architecture decision records describe consequential decisions visible in the current implementation. These records are retrospective where the code predates the handbook.

The consolidated status and ownership view is maintained in the [Architecture Decision Register](../Decision-Register.md).

[ADR-000 — Founding Principles](../01-Architecture/ADR-000-Founding-Principles.md) records the governance decision that every feature must strengthen the ecosystem, not simply an individual product. It is retained with the strategic architecture foundation at the path established by the operating model.

| ADR | Decision | Status |
| --- | --- | --- |
| [ADR-000](../01-Architecture/ADR-000-Founding-Principles.md) | Every feature must strengthen the ecosystem, not simply an individual product | Accepted |
| [ADR-001](ADR-001-organisation-is-the-root-domain.md) | Organisation is the root domain | Accepted |
| [ADR-002](ADR-002-uuid-primary-keys.md) | UUID primary keys | Accepted |
| [ADR-003](ADR-003-postgresql-selected-for-encore.md) | PostgreSQL selected for Encore | Accepted |
| [ADR-004](ADR-004-api-first-architecture.md) | API-first architecture | Accepted |
| [ADR-005](ADR-005-service-layer-architecture.md) | Service layer architecture | Accepted — incremental adoption |
| [ADR-006](ADR-006-provider-neutral-integrations.md) | Provider-neutral integrations | Accepted |
| [ADR-007](ADR-007-event-driven-processing.md) | Event-driven processing | Proposed |
| [ADR-008](ADR-008-queue-first-background-processing.md) | Queue-first background processing | Proposed |
| [ADR-009](ADR-009-anchor-verification-at-performance-level.md) | Anchor verification at performance level | Accepted |
| [ADR-010](ADR-010-transactional-idempotent-provider-upserts.md) | Transactional idempotent provider upserts | Accepted |
| [ADR-011](ADR-011-signed-provider-event-ingestion.md) | Signed provider event ingestion | Accepted |
| [ADR-012](ADR-012-transactional-administrative-audit-logging.md) | Transactional administrative audit logging | Accepted |
| [ADR-013](ADR-013-policy-led-tenant-authorisation.md) | Policy-led tenant authorisation | Accepted |
| [ADR-014](ADR-014-provider-event-store.md) | Provider event store | Accepted |
| [ADR-015](ADR-015-authority-through-verification.md) | Authority through verification | Accepted |

## ADR lifecycle

- **Proposed**: under review and not yet authoritative.
- **Accepted**: implemented or approved as the current direction.
- **Superseded**: replaced by a later ADR; retained for history.
- **Deprecated**: still present but scheduled for removal.

An accepted ADR should not claim controls or capabilities absent from code. A material reversal should add a new ADR and mark the previous record superseded.
