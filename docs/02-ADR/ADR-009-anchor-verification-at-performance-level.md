# ADR-009: Anchor Verification at Performance Level

- Status: Accepted
- Date: 2026-07-15
- Scope: Reviews, invitations, shows, and performances

## Context

A show may occur many times, at different times or venues. Attendance evidence applies to a specific occurrence, not merely to the show title.

## Decision

Attach both review invitations and reviews to a Performance.

An invitation identifies the performance and optionally carries provider booking, ticket, and attendance metadata. Consuming an invitation creates a review for the same performance. Show-level review lists and aggregates traverse performances using Eloquent `hasManyThrough`.

## Consequences

- Verification has a precise event occurrence.
- A show can aggregate reviews across all of its performances.
- Venue and timing context remain available for future performance-aware reporting.
- Provider integrations must synchronize the show before its performances and the performance before its invitations.
- Deleting a performance cascades to its invitations and reviews under the current schema.

## Alternatives considered

### Attach reviews directly to shows

Rejected because it loses the specific occurrence used to establish attendance and venue/time context.

### Attach reviews to bookings or tickets

Rejected as the core relationship because booking and ticket identifiers are provider-specific. They remain invitation metadata.
