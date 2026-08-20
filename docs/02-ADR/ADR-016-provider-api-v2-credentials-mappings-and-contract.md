# ADR-016: Provider API v2 Credentials, Mappings, and Contract

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-08-04
- Scope: Provider identity, credentials, tenant authority, external mappings,
  request authentication, idempotency, API versioning and v1 compatibility
- Extends: ADR-001, ADR-003, ADR-004, ADR-005 and ADR-013
- Partially supersedes: ADR-006, ADR-010, ADR-011 and ADR-014

## Context

Encore currently exposes unversioned TicketPal routes authenticated with one
application-wide shared secret plus signed event headers. Delivery identity is
stored in `integration_events`, while provider show/performance identifiers are
stored directly on core catalogue rows. Provider requests may supply Encore
`organisation_id`.

These controls established a valuable foundation but do not meet the Release 1
boundary:

- one credential has platform-wide impact and no key ID or overlap rotation;
- provider identity and tenant authority are not derived from scoped
  credentials;
- no nonce distinguishes transport replay from intentional business retry;
- delivery event identity doubles as the idempotency mechanism;
- the signature does not bind the HTTP path;
- provider identifiers on core rows make a second provider progressively more
  invasive;
- TicketPal and Encore need one versioned, testable contract for consented
  eligibility and withdrawal.

The repository's Provider API Specification v2 is proposed but not activated.
The Release 1 hand-off therefore provides the first concrete Provider API v2
capability.

## Decision

### Versioned boundary

Adopt Encore Provider API v2 for new Release 1 provider operations.

Initial operations:

- `POST /api/v2/integrations/review-invitation-eligibilities`;
- `POST /api/v2/integrations/review-invitation-withdrawals`.

The normative contract is an approved OpenAPI document with immutable shared
TicketPal/Encore fixtures. A contract change is additive or receives a new
version and fixture set.

### Provider credentials

Authenticate providers using a credential selected by
`X-Provider-Key-Id`. Each credential records:

- provider identity;
- allowed operations;
- allowed organisations/provider-account scope where applicable;
- activation, expiry and revocation times;
- secret reference, not plaintext HMAC secret;
- rotation lineage.

Provider and tenant authority are derived from the authenticated credential and
resolved mappings. A body/header provider or organisation value never grants
authority and must agree with resolved context if present.

TicketPal is the first provider, not a special core-domain identity.

### Provider-neutral mappings

Use explicit external organisation, show and performance mapping records. A
mapping is unique by provider, credential/account scope, resource type and
external identifier. It resolves to one Encore resource and organisation.

Provider identifiers currently stored on core rows become compatibility and
backfill sources. New v2 review-domain records do not add TicketPal-specific ID
columns. Reassignment is an explicit audited command, never an ordinary upsert.

Encore never reads TicketPal's database. TicketPal never writes Encore's
database.

### Request proof

Require:

- `X-Provider-Key-Id`;
- `X-Request-Timestamp` as RFC 3339 UTC;
- `X-Request-Nonce` as UUID;
- `X-Request-Signature` as `v1=<64 lowercase HMAC-SHA256 hex>`;
- `Idempotency-Key`;
- `X-Correlation-Id` as UUID.

Canonical signature input:

```text
METHOD + "\n" + ABSOLUTE_PATH + "\n" + X-Request-Timestamp + "\n" +
X-Request-Nonce + "\n" + lowercase_hex(SHA256(raw_request_body))
```

`ABSOLUTE_PATH` includes `/api/v2` and excludes scheme, authority, query and
fragment. The canonical string has no final newline. Verification uses the
exact raw body and constant-time comparison.

Timestamp freshness is checked against the approved window. A nonce is
atomically single-use for its credential retention scope.

### Idempotency

Scope idempotency by credential, operation and idempotency key.

- Same key and request digest: return HTTP 202 with `duplicate`, stable event/
  resource identifiers and the current correlation ID.
- Same key and different digest: return HTTP 409
  `idempotency_conflict`, with no domain mutation.
- Replayed nonce: return safe HTTP 401 before idempotency/domain processing.
- Domain uniqueness independently prevents a second eligibility for the same
  provider booking and consent purpose.

Persist minimised inbound security evidence, provider-event processing state and
idempotency outcome. Do not retain raw signed request bodies in ordinary logs or
security journals.

### Compatibility

Existing `/api/ticketpal/shows/upsert` and
`/api/ticketpal/performances/upsert` may remain temporarily as compatibility
routes while mappings are backfilled. They do not define the v2 credential or
review-domain model.

The current `/api/ticketpal/invitations` operation must not be used for the new
Release 1 hand-off once ADR-017 becomes authoritative.

Compatibility endpoints are feature-controlled, monitored and retired only
after zero-use evidence and separate approval. No silent dual-write of
invitation state is permitted.

## Consequences

- TicketPal must implement the v2 signer, new nonce per attempt, idempotency key
  policy and correlation propagation.
- Encore must introduce credential, mapping, nonce/idempotency and request-
  journal storage additively.
- Signature fixtures change when the signed path or raw JSON bytes change.
- Provider retries use a fresh nonce while retaining the same idempotency key
  and request content.
- Credential rotation can overlap without a platform-wide secret cutover.
- Mapping conflicts become explicit operational exceptions.
- Existing catalogue columns remain during a compatibility/reconciliation
  window and are not removed in the first migration.
- v1 and v2 authentication may coexist temporarily, increasing operational
  complexity; each route has one unambiguous contract.
- Current encrypted replay bodies are not the v2 idempotency authority; stable
  outcomes are recorded without needing to retain an invitation token.

## Alternatives considered

### Extend the existing TicketPal headers and routes in place

Rejected because the change is breaking, remains provider-specific and would
make two materially different contracts appear to be one unversioned API.

### Use `X-Provider` as the credential

Rejected because a provider name is declarative and does not prove identity,
scope, revocation state or tenant authority.

### Continue storing provider IDs directly on each core table

Rejected as the Release 1 target because each future provider would expand core
schema and conflict logic. Existing columns remain only for compatibility and
backfill.

### Use event ID alone for replay and idempotency

Rejected because transport replay and intentional retry are different concerns.
A nonce proves one HTTP attempt; an idempotency key protects one business
operation; an event ID identifies the provider fact.

### Direct database integration

Rejected because it bypasses ownership, validation, tenant controls, audit and
independent availability.

## Security and privacy implications

- Secrets remain in approved secret storage and must not appear in database
  business columns, logs, fixtures or error responses.
- Credential lookup, timestamps and signatures fail before domain work.
- Provider/operation rate limits and payload limits are required.
- Request, nonce, idempotency and event retention require privacy/operations
  approval.
- Reviewer PII is limited to the approved eligibility payload and protected by
  ADR-017's identity/contact design.

## Migration implications

1. Inventory live PostgreSQL schema and current provider-column conflicts.
2. Add provider/credential/mapping tables and seed TicketPal idempotently.
3. Backfill mappings and quarantine conflicts.
4. Add v2 ingress/idempotency tables and disabled routes.
5. Run shared v2 fixtures in both repositories.
6. Enable v2 ingress by credential/organisation scope.
7. Retain v1 catalogue paths until later migration evidence supports retirement.

## Acceptance conditions

- Approved Provider API v2 OpenAPI and shared fixture version exist.
- Every TicketPal external identifier used by v2 resolves through one tenant-
  safe mapping.
- Unknown, revoked, expired, stale, replayed and out-of-scope requests create no
  domain state.
- Duplicate/conflict fixtures behave deterministically under concurrency.
- TicketPal and Encore provider/consumer contract suites pass.
- Credential rotation/revocation and operational ownership are documented.
- No direct TicketPal database access or TicketPal-specific review-domain field
  is introduced.
