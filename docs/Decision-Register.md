# Architecture Decision Register

- Register owner: Encore Reviews Engineering
- Baseline: v0.3.0 Enterprise Foundation
- Last reviewed: 16 July 2026

This register is the concise index of architectural decisions made or proposed to date. The linked ADR is the authoritative decision record. Status changes must update both the ADR and this register in the same change.

| Decision ID | Title | Related ADR | Status | Date | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| ADR-000 | Founding Principles | [ADR-000](01-Architecture/ADR-000-Founding-Principles.md) | Accepted | 2026-07-24 | Encore Leadership | Every feature must strengthen the ecosystem; TicketPal and Encore retain standalone value and create greater value together. |
| ADR-001 | Organisation Is the Root Domain | [ADR-001](02-ADR/ADR-001-organisation-is-the-root-domain.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Organisation governs tenancy and ownership; providers remain integrations. |
| ADR-002 | UUID Primary Keys | [ADR-002](02-ADR/ADR-002-uuid-primary-keys.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | UUIDs are the domain identifier standard; legacy Laravel user ID remains numeric. |
| ADR-003 | PostgreSQL Selected for Encore | [ADR-003](02-ADR/ADR-003-postgresql-selected-for-encore.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | PostgreSQL is the authoritative deployed relational database. |
| ADR-004 | API-First Architecture | [ADR-004](02-ADR/ADR-004-api-first-architecture.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Integration capabilities are governed contracts independent of UI delivery. |
| ADR-005 | Service Layer Architecture | [ADR-005](02-ADR/ADR-005-service-layer-architecture.md) | Accepted — incremental adoption | 2026-07-15 | Encore Reviews Engineering | Business logic moves to services as relevant workflows are changed. |
| ADR-006 | Provider-Neutral Integrations | [ADR-006](02-ADR/ADR-006-provider-neutral-integrations.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Provider terminology is confined to integration boundaries. |
| ADR-007 | Event-Driven Processing | [ADR-007](02-ADR/ADR-007-event-driven-processing.md) | Proposed | 2026-07-15 | Encore Reviews Engineering | No implemented domain-event publication guarantee; approval remains pending. |
| ADR-008 | Queue-First Background Processing | [ADR-008](02-ADR/ADR-008-queue-first-background-processing.md) | Proposed | 2026-07-15 | Encore Reviews Engineering | No implemented business jobs or production worker operating model. |
| ADR-009 | Anchor Verification at Performance Level | [ADR-009](02-ADR/ADR-009-anchor-verification-at-performance-level.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Invitations and reviews relate to the attended performance. |
| ADR-010 | Transactional, Idempotent Provider Upserts | [ADR-010](02-ADR/ADR-010-transactional-idempotent-provider-upserts.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Provider show/performance writes use transactions and stable identities. |
| ADR-011 | Signed Provider Event Ingestion | [ADR-011](02-ADR/ADR-011-signed-provider-event-ingestion.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | TicketPal payloads require HMAC signature, event identity, and freshness. |
| ADR-012 | Transactional Administrative Audit Logging | [ADR-012](02-ADR/ADR-012-transactional-administrative-audit-logging.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Privileged mutations and audit evidence commit together where applicable. |
| ADR-013 | Policy-Led Tenant Authorisation | [ADR-013](02-ADR/ADR-013-policy-led-tenant-authorisation.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Policies authorize administrative access; queries remain explicitly scoped. |
| ADR-014 | Provider Event Store | [ADR-014](02-ADR/ADR-014-provider-event-store.md) | Accepted | 2026-07-15 | Encore Reviews Engineering | Persistent provider delivery lifecycle, idempotency, replay, and correlation evidence. |
| ADR-015 | Authority Through Verification | [ADR-015](02-ADR/ADR-015-authority-through-verification.md) | Accepted | 2026-07-24 | Encore Product and Engineering | Identity grants access; explicit verification grants trusted contribution authority. Extends ADR-009. |

## Governance

- Accepted decisions are part of the v0.3.0 baseline unless a later ADR supersedes them.
- Proposed decisions are planning constraints, not implementation authority.
- Reversals must add a superseding ADR rather than silently rewriting history.
- Decisions affecting provider contracts must also update the [Provider API Specification](03-API/Provider-API-Specification-v2.md).
- The current status vocabulary is defined by the [ADR index](02-ADR/README.md).
