# ADR-005: Service Layer Architecture

- Status: Accepted — incremental adoption
- Date: 2026-07-15
- Scope: Application business logic

## Context

Provider synchronization and review workflows combine validation, entity resolution, transactions, idempotency, and domain failures. Keeping multi-step behavior in HTTP controllers makes it difficult to reuse, test independently, or invoke later from queues and commands.

The current codebase is transitional. `PerformanceSyncService` implements the service-layer pattern, while show synchronization, invitation creation, review submission, and some administration workflows remain controller-led.

## Decision

Use focused application services for multi-step business workflows and transactional orchestration.

Controllers should remain delivery adapters: accept validated input, invoke an application service, and format an HTTP response or view. Eloquent models retain relationships, casts, and persistence behavior; they should not become general-purpose workflow containers.

Adoption is incremental. Existing controller-led workflows should move to services when they gain complexity or are materially changed, rather than through a behavior-changing rewrite solely for layering consistency.

## Consequences

- Complex logic becomes reusable from HTTP, commands, scheduled tasks, or queued jobs.
- Transaction boundaries and domain failures have an explicit home.
- Services can be tested without coupling every rule to response formatting.
- More classes and dependency boundaries must be maintained.
- Small CRUD operations may remain controller-led when a service adds no meaningful separation.
- The architecture must not falsely describe all current workflows as service-based during the transition.

## Alternatives considered

### Fat controllers

Rejected as the target architecture because delivery concerns and business transactions become tightly coupled.

### Fat Eloquent models

Rejected for cross-aggregate workflows because models would accumulate orchestration unrelated to one entity's persistence behavior.

### Repository layer for every model

Not selected. Eloquent already provides the persistence abstraction currently required, and mandatory repositories would add ceremony without solving workflow orchestration.

### Immediate full rewrite

Rejected because it would create unnecessary regression risk. Incremental extraction preserves tested behavior.
