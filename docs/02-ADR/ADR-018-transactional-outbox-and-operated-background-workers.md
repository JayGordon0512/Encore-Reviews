# ADR-018: Transactional Outbox and Operated Background Workers

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-08-04
- Scope: Domain events, post-commit work, queues, retries, dead letters,
  observability and failure independence
- Supersedes: Proposed ADR-007 and proposed ADR-008
- Depends on: ADR-016 and ADR-017

## Context

The repository contains queue tables/configuration but no business jobs,
transactional outbox or worker operations. Implemented provider flows are
synchronous. Proposed ADR-007 and ADR-008 describe event-driven and queue-first
directions but deliberately defer delivery guarantees, transaction integration,
failure handling and operational proof.

Release 1 introduces work that must happen after authoritative state commits:

- invitation and reminder delivery;
- moderation/publication reactions;
- reputation recalculation and search projection;
- delivery of permitted reputation output;
- retries, reconciliation and dead-letter recovery.

Dispatching a queue job before commit can expose uncommitted/missing state.
Relying on an in-process after-commit callback alone can lose work if the process
terminates after commit. Reliable intent must be durable in PostgreSQL.

## Decision

### Transactional outbox

Use a PostgreSQL transactional outbox for durable cross-workflow events and
external side-effect intent.

The originating application service writes aggregate state, audit evidence and
outbox message in the same transaction. An outbox message contains:

- event ID and schema version;
- past-tense domain event type;
- aggregate type/ID;
- organisation/provider context where applicable;
- occurred time and correlation ID;
- minimised versioned payload;
- delivery state, attempts and availability time.

No network call or email occurs inside the domain transaction.

### Publisher and queue

An operated publisher claims pending outbox rows safely using PostgreSQL locking
semantics, then dispatches/executes handlers through Laravel's queue
infrastructure. Initial operation may use the database queue; a broker change
does not change the domain contract.

Queue configuration for these workloads dispatches only after authoritative
commit. Generic Laravel `after_commit=false` defaults must not cause Release 1
jobs to publish before commit.

Outbox publication is at least once. Exactly-once external delivery is not
claimed; consumers are idempotent by event/source ID and database constraints.

### Worker contract

Every business job defines:

- identifier-only payload and correlation context;
- queue and priority;
- timeout;
- maximum attempts;
- backoff with jitter;
- idempotency key/state check;
- retryable versus terminal error classes;
- dead-letter behaviour;
- metrics and alert threshold;
- safe manual inspect/retry/resolve operation.

Jobs reload authoritative state and safely accept already-completed work.
Eloquent models, contact PII, raw provider payloads, secrets and invitation
tokens are not placed in generic queue payloads.

### Initial Release 1 handlers

- issue due review invitation;
- send token-free notification delivery intent through ADR-017's secure issuing
  process;
- expire invitations and schedule approved reminders;
- request publication after moderation approval;
- recalculate reputation after publication/takedown;
- update authorised search/public projections;
- deliver permitted reputation output to providers;
- retry provider-event processing/reconciliation where approved.

### Dead letters and recovery

Bounded retries end in an explicit dead-letter state with safe error code,
attempt history, correlation ID and operational owner. Dead letters do not
silently disappear into generic failed-job storage.

Operations can inspect, retry or resolve without editing domain rows directly.
Every action is audited. Reconciliation can rebuild projections and find
committed state with missing/incomplete delivery.

### Failure independence

TicketPal commits sales, payments, fulfilment and its outbox independently of
Encore. Encore commits accepted eligibility independently of email/search/
provider-output availability. An outage creates observable backlog/retry state,
not a cross-platform transaction.

## Consequences

- PostgreSQL stores additional operational rows and needs retention/cleanup.
- Queue workers become required Release 1 runtime infrastructure.
- At-least-once processing requires explicit handler idempotency and duplicate
  tests.
- Publication and reputation may become eventually consistent by a bounded,
  observable delay.
- Monitoring must cover outbox age, queue depth, attempts, failures, dead
  letters, worker health and handler latency.
- Deployments must account for old/new workers and event schema compatibility.
- External side-effect failures no longer roll back the originating domain fact.
- The presence of an outbox row is not proof the external consumer accepted it;
  outbound delivery state remains distinct.

## Alternatives considered

### Dispatch Laravel jobs directly inside transactions

Rejected because a worker can observe missing/uncommitted state and rollback can
leave a job for a fact that never committed.

### Use only Laravel after-commit callbacks

Rejected for durable integration intent because process failure after commit can
lose the callback.

### Execute email/search/provider delivery synchronously

Rejected because external availability would increase request latency and
couple core workflows to secondary systems.

### Adopt Kafka or another broker immediately

Not selected. The transactional boundary and event contracts are the important
decision. Laravel's database queue/outbox should be proven before adding broker
operations.

### Event sourcing

Not selected. PostgreSQL aggregate tables remain authoritative; outbox events
communicate committed facts and do not rebuild all domain state.

## Security and privacy implications

- Outbox/job payload schemas are allowlisted and minimised.
- Tokens, secrets, raw bodies and unnecessary PII are prohibited.
- Failed-job/dead-letter stores receive the same access and retention controls
  as integration evidence.
- Manual retry/resolve requires platform operations authority and audit.
- Worker identities receive least-privilege database/queue access.

## Operational requirements

- named worker/dead-letter/on-call owner;
- supervised worker deployment and health check;
- retry/backoff/dead-letter runbook;
- backup/restore and outbox reconciliation proof;
- alerts for backlog age and terminal failures;
- feature flags for ingress, invitation issuing, publication and provider
  delivery;
- graceful deployment/rollback compatible with queued schema versions.

## Acceptance conditions

- Transaction rollback leaves no publishable outbox event.
- Committed event survives application-process interruption before publication.
- Duplicate handler execution produces one domain/projection effect.
- Retryable failure recovers without repeating source state changes.
- Terminal failure enters visible dead letter and is safely reprocessed through
  the runbook.
- No prohibited sensitive value is found in outbox, queues, failed jobs, logs or
  audit.
- Simulated Encore/email/search/provider outage preserves TicketPal and Encore
  authoritative core flows.
- G4 rehearsal proves worker monitoring, backup/restore and reconciliation.
