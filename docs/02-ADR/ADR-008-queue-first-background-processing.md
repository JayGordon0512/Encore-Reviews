# ADR-008: Queue-First Background Processing

- Status: Proposed
- Date: 2026-07-15
- Scope: Non-interactive and retryable workloads

## Context

Provider synchronization orchestration, invitation delivery, analytics aggregation, reconciliation, and other future workloads may be slow, retryable, or independent from an interactive HTTP response.

Laravel's database queue configuration and jobs tables exist, and the development Composer workflow can start a queue listener. The application currently defines no business jobs and executes implemented API workflows synchronously.

## Decision

Propose queue-first execution for non-interactive side effects and background workloads.

Request-critical validation and authoritative state changes should remain synchronous when the caller requires an immediate outcome. Work that can be retried independently should be dispatched after the relevant database transaction commits. Jobs must be idempotent, bounded, observable, and configured with explicit retry and failure behavior.

This ADR remains Proposed until the first production background workflow establishes worker deployment, supervision, retry policy, dead-letter handling, alerting, and operational recovery.

## Consequences

- Interactive requests can avoid waiting for email, analytics, or reconciliation work.
- Transient failures can be retried without repeating the originating request.
- Queue workers become required runtime infrastructure for queued capabilities.
- At-least-once processing requires idempotency and duplicate-delivery tests.
- Failed jobs, backlog depth, processing latency, and worker health require monitoring.
- The presence of queue tables alone must not be documented as implemented background processing.

## Alternatives considered

### Execute all work synchronously

Retained for current request-critical workflows but rejected as the long-term approach for slow or independently retryable work.

### Cron-only batch processing

Useful for scheduled reconciliation but insufficient for prompt per-event side effects and granular retries.

### External message broker immediately

Not selected before workload and delivery requirements are known. Laravel's configured queue abstraction should be proven first.

### Fire-and-forget processes from HTTP requests

Rejected because they lack reliable acknowledgement, supervision, retry behavior, and operational visibility.
