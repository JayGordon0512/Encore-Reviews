# ADR-007: Event-Driven Processing

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-07-15
- Scope: Cross-workflow reactions and integration side effects

## Context

Encore's workflows produce meaningful business facts: shows and performances are synchronized, invitations are created or consumed, reviews are submitted, and moderation status changes. Future capabilities such as email delivery, analytics, audit trails, reconciliation, and widgets may need to react without increasing coupling inside the originating transaction.

The current application does not define domain event classes, listeners, an outbox, or event publication guarantees. Existing workflows are synchronous.

## Decision

Propose domain events as the standard mechanism for secondary reactions to completed business facts.

Events should use past-tense domain language, carry stable Encore identifiers, and be emitted only after the originating transaction commits. The source workflow remains responsible for its core state change; listeners handle independent reactions.

This ADR does not authorize an event-sourcing rewrite. It remains Proposed until the first event-backed capability defines delivery guarantees, transaction integration, failure handling, observability, and tests.

## Consequences

- Future side effects can evolve without adding direct dependencies to core write workflows.
- Event payloads become durable internal contracts requiring governance.
- At-least-once delivery would require idempotent listeners.
- Transactional publication needs an explicit design, potentially an outbox, before events can be considered reliable.
- The current synchronous implementation remains authoritative until this ADR is accepted and implemented.

## Alternatives considered

### Direct synchronous calls for every reaction

Simple initially but increases coupling, request latency, and cascading failure risk as reactions grow.

### Database triggers

Rejected as the application event mechanism because behavior would be hidden from Laravel workflows and harder to test and operate consistently.

### Event sourcing

Not selected. Rebuilding aggregate state from an event log is substantially broader than the current need for post-transaction notifications.

### Polling tables without domain events

Possible for isolated integrations but loses explicit domain semantics and can duplicate reaction logic.
