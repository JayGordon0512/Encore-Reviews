# ADR-014: Provider Event Store

- Status: Accepted
- Date: 2026-07-15
- Scope: Provider delivery ingestion, idempotency, replay, and processing lifecycle

## Context

Provider deliveries may be repeated, concurrent, delayed, or ambiguous after a network failure. Show and performance upserts are naturally repeatable, but invitation creation is not. Encore needs one authoritative record that prevents duplicate processing and gives provider support a stable correlation identifier.

This store records delivery processing; it is not a domain-event log and does not accept ADR-007 or ADR-008.

## Decision

Persist each authenticated provider request in `integration_events`, uniquely identified by `provider + external_event_id`. The record contains event type, SHA-256 payload hash, receipt and processing times, lifecycle status, bounded attempt count, sanitized error classification, and a generated correlation UUID.

The lifecycle is:

1. Validate shared-secret authentication, timestamp freshness, and payload signature.
2. Insert a `processing` record before controller validation and domain work.
3. Mark non-server responses `processed`, storing the HTTP status and an application-encrypted response body.
4. Mark thrown exceptions and HTTP 5xx responses `failed` without storing exception messages or stack traces.
5. Permit a matching failed event to retry up to the configured maximum of three attempts.

A duplicate with the same payload never repeats domain processing. A processed duplicate receives the original decrypted response while its seven-day replay retention is valid. A concurrent `processing` event, exhausted failure, unavailable response, or expired response returns HTTP 409. Reuse of the same event ID with a different payload returns HTTP 409.

Raw request payloads are not retained. Only the one-way payload hash is stored. Replay response bodies are encrypted with the Laravel application key, hidden from model serialization, and retained for the configured seven-day window. Each response exposes `X-Correlation-ID`; replayed responses also expose `X-Provider-Event-Replayed: true`.

## Consequences

- Duplicate invitation processing is prevented alongside show and performance duplicate processing.
- The database uniqueness constraint is the concurrency authority.
- Replay availability depends on stable access to the application encryption key.
- Key loss or rotation without a compatibility plan makes retained responses unavailable but does not repeat completed processing.
- A cleanup process for expired encrypted responses and long-term event retention is not yet implemented.
- Event status alone is operational evidence, not a substitute for domain audit history or an event-sourcing ledger.

## Alternatives considered

### Cache-only idempotency

Rejected because eviction, restart, and cache topology could allow duplicate durable writes.

### Store raw provider payloads

Rejected for the current requirement because it increases personal-data, security, and retention exposure. Payload hashes are sufficient to detect event-ID conflicts.

### Return a generic success for duplicates

Rejected because invitation creation returns a one-time raw token. Replaying the encrypted original response preserves the provider contract without creating another invitation.

### Process asynchronously through a queue

Not selected in Sprint 0. Current contracts require synchronous responses, and ADR-008 remains Proposed pending worker operations and failure semantics.
