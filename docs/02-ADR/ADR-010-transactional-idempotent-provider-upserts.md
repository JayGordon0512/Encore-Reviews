# ADR-010: Transactional, Idempotent Provider Upserts

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Accepted
- Date: 2026-07-15
- Scope: TicketPal show and performance synchronization

## Context

Provider deliveries may be repeated. Creating a new record for every delivery would duplicate shows, performances, and venues and would break review ownership.

## Decision

Implement provider synchronization as upserts using stable provider keys:

- Show: `provider_source + provider_event_id`
- Performance: `provider_source + provider_performance_id`
- Venue resolution: `organisation_id + slug`

Use database transactions and row locking around synchronization. Enforce the provider show key, provider performance key, and organisation venue slug key with database uniqueness constraints. Keep performance synchronization business logic in `PerformanceSyncService`; normalize incoming performance timestamps to UTC.

## Consequences

- Re-delivery updates the existing record and reports `created: false`.
- Conflicting provider performance ownership is rejected instead of silently moving a performance to a different show.
- A performance cannot synchronize until its show exists and belongs to an organisation.
- Venue names that normalize to the same slug within one organisation resolve to one venue.
- Show upsert remains controller-led; the service-layer pattern is not yet universal.

## Alternatives considered

### Create a new record for every delivery

Rejected because provider retries would duplicate domain records.

### Last-write-wins without stable provider keys

Rejected because Encore could not reliably identify which record a provider update targets.

### Provider-managed Encore primary keys

Rejected because Encore UUID identity must remain independent from provider identity.
