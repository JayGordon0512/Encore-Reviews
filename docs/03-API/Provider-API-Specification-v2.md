# Provider API Specification v2

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Document status: Proposed target contract
- Contract status: Not implemented and not approved for production use
- Current production contract: [HTTP API Reference](README.md)
- Owner: Encore Reviews Engineering
- Last reviewed: 15 July 2026

## Document control

| Field | Value |
| --- | --- |
| Document version | 2.0-draft |
| Contract effective date | Not assigned |
| Encore architecture approval | Pending |
| Encore security approval | Pending |
| Provider representative approval | Pending |
| Supersedes | No approved provider-neutral specification |

Approval changes the document status; it does not make unimplemented routes operational. Activation requires implementation and the acceptance evidence defined by this specification.

## 1. Purpose

Provider API v2 defines the proposed provider-neutral contract through which ticketing and event systems will deliver show, performance, attendance, and ticket lifecycle data to Encore Reviews.

The contract is governed by the [Operating Principles](../00-Vision/Operating-Principles.md) and [ADR-000](../01-Architecture/ADR-000-Founding-Principles.md): integrations should strengthen the ecosystem, TicketPal and Encore must retain standalone value, and Encore remains an open, provider-agnostic platform.

Version 2 exists to replace provider-specific authentication and naming with a governed enterprise interface. Its intended controls are payload authentication, delivery identity, replay prevention, correlation, explicit ownership, bounded retries, and consistent error behavior. These controls reduce duplicate writes, payload tampering, ambiguous recovery, and provider coupling.

This document deliberately separates the implemented TicketPal interface from the proposed v2 target. It does not activate routes or authorize implementation. Before v2 code is written, the initiative must complete Strategic Review, Engineering Review, and Founder Approval; this specification must be approved; unresolved contract decisions must be closed; and any architectural changes must have an accepted ADR.

## 2. Status language

The key words **MUST**, **MUST NOT**, **REQUIRED**, **SHOULD**, and **MAY** are normative only for the proposed v2 target. They do not describe current Encore behavior unless a section is marked **Current implementation**.

| Classification | Meaning |
| --- | --- |
| Current | Implemented, tested behavior described by the current API reference |
| Proposed | Intended v2 behavior requiring contract approval and implementation |
| Decision required | Material detail intentionally left undefined; implementation is prohibited until resolved |
| Future recommendation | Non-contractual direction requiring later design |

## 3. Provider model

### 3.1 Supported providers

**Current:** TicketPal is the only implemented provider integration.

**Proposed:** v2 will identify integrations by a stable provider identifier. Candidate future providers include Eventbrite, TicketSource, Ticketsolve, Skiddle, and explicitly approved custom integrations. These examples do not imply support, certification, or scheduled delivery.

Provider names MUST remain at the integration boundary. Encore's ownership root remains `Organisation`; a provider MUST NOT define core domain terminology or acquire ownership of Encore records. See [ADR-001](../02-ADR/ADR-001-organisation-is-the-root-domain.md) and [ADR-006](../02-ADR/ADR-006-provider-neutral-integrations.md).

### 3.2 Interaction model

Providers send HTTPS JSON requests to an Encore endpoint. Encore authenticates the delivery, registers its event identity, validates the payload, performs the synchronous operation, and returns a JSON response with a correlation identifier. Duplicate delivery semantics are governed by [ADR-014](../02-ADR/ADR-014-provider-event-store.md).

v2 does not introduce asynchronous acknowledgement, domain-event publication, or queue processing. [ADR-007](../02-ADR/ADR-007-event-driven-processing.md) and [ADR-008](../02-ADR/ADR-008-queue-first-background-processing.md) remain Proposed.

## 4. Contract baseline

| Concern | Current TicketPal contract | Proposed v2 target |
| --- | --- | --- |
| Route namespace | `/api/ticketpal/*` | Decision required: versioned provider-neutral URI |
| Provider identification | Implied as `ticketpal` by route/middleware | `X-Provider` |
| Legacy credential | `X-TicketPal-Secret` required | Decision required for credential identifier and rotation |
| Event identity | `X-TicketPal-Event-ID` | `X-Provider-Event-ID` |
| Timestamp | `X-TicketPal-Timestamp` | `X-Provider-Timestamp` |
| Signature | `X-TicketPal-Signature` | `X-Provider-Signature` |
| Correlation | Encore generates response `X-Correlation-ID` | Provider supplies request `X-Correlation-ID`; Encore validates and echoes it |
| Clock tolerance | ±300 seconds by default | ±300 seconds |
| Replay store | Implemented | Retained, provider-scoped |

## 5. Proposed v2 authentication

### 5.1 Required headers

Every v2 request MUST provide:

| Header | Proposed requirement |
| --- | --- |
| `X-Provider` | Stable, lowercase provider identifier assigned by Encore |
| `X-Provider-Event-ID` | Provider-unique immutable delivery ID, maximum 255 characters |
| `X-Provider-Timestamp` | Ten-digit Unix timestamp in UTC seconds |
| `X-Provider-Signature` | Lowercase hexadecimal HMAC-SHA256, optionally prefixed by `sha256=` |
| `X-Correlation-ID` | UUID identifying the end-to-end operation |

**Decision required:** v2 credential lookup, key identifiers, provider/organisation scope, and overlapping rotation are not yet approved. `X-Provider` MUST NOT be treated as proof of identity by itself.

### 5.2 Signature generation

The proposed canonical signed bytes are:

```text
<timestamp>.<provider-event-id>.<raw-request-body>
```

The sender computes:

```text
hex(HMAC-SHA256(provider-secret, canonical-bytes))
```

The raw body means the exact transmitted bytes after JSON serialization. Reformatting JSON, changing whitespace, changing key order, or changing character encoding after signing changes the signature. Both sides MUST use UTF-8 JSON and compare signatures in constant time.

**Current implementation:** TicketPal uses the same canonical construction with `X-TicketPal-*` headers and the application-wide TicketPal secret. It additionally requires `X-TicketPal-Secret`. See [ADR-011](../02-ADR/ADR-011-signed-provider-event-ingestion.md).

### 5.3 Freshness and replay protection

The proposed v2 timestamp MUST fall within ±300 seconds of Encore's receiving clock. Providers MUST synchronize clocks through a reliable time source. A valid signature with an expired timestamp is rejected with HTTP 401 before event registration.

After authentication, Encore computes a SHA-256 hash of the raw payload and registers `provider + external event ID`. The database uniqueness constraint is authoritative.

## 6. Replay semantics

| Condition | Current behavior and proposed v2 baseline |
| --- | --- |
| First valid delivery | Register as `processing`, execute once, then record `processed` or `failed` |
| Same event ID and identical payload, original processed and retained | Return original status/body without executing again; include `X-Provider-Event-Replayed: true` |
| Same event ID and identical payload, currently processing | HTTP 409, `Retry-After: 1`, no duplicate processing |
| Same event ID and different payload hash | HTTP 409; never process the conflicting payload |
| Failed event below attempt limit | Permit another processing attempt; current maximum is three total attempts |
| Failed event at attempt limit | HTTP 409; automatic processing does not resume |
| Processed event whose replay response expired/unavailable | HTTP 409; do not repeat completed domain work |

Current response replay retention is seven days by default. Raw provider payloads are not retained; encrypted original response bodies and payload hashes are retained. See [ADR-014](../02-ADR/ADR-014-provider-event-store.md).

## 7. Endpoint catalogue

### 7.1 Show Upsert

**Status:** Implemented for TicketPal; proposed for provider-neutral v2.

**Current route:** `POST /api/ticketpal/shows/upsert`

**Purpose:** Create or update the provider's stable representation of a show.

#### Request

| Field | Requirement | Validation/current behavior |
| --- | --- | --- |
| `provider_event_id` | Required | String; provider show identity in the current contract |
| `title` | Required | String |
| `ticket_url` | Required | Valid URL |
| `slug` | Optional | String; used at creation or to fill an empty existing slug |
| `summary` | Optional | Nullable string |
| `description` | Optional | Nullable string |
| `genre` | Optional | Nullable string |
| `status` | Optional | `upcoming`, `now_playing`, or `archived`; defaults to `upcoming` on creation |
| `primary_image_path` | Optional | Nullable string |
| `ticket_url_source` | Optional | Nullable string; current default `ticketpal` |
| `organisation_id` | Optional | Nullable existing Encore Organisation UUID |

**Security:** Current TicketPal authentication and signed event headers are required. The v2 target will use Section 5 after approval.

**Idempotency:** Delivery idempotency uses the provider event store. Domain upsert identity is current `ticketpal + provider_event_id`.

**Success:** HTTP 200 with `ok`, `created`, and the Encore show representation. Both creation and update currently return 200.

**Errors:** Authentication failures return 401; delivery conflicts return 409; validation and organisation lookup failures return 422; unhandled failures return 500.

**Decision required for v2:** distinguish delivery event ID from stable provider show ID, approve the provider-neutral route, define whether providers may assign `organisation_id`, and bound all currently unbounded strings.

### 7.2 Performance Upsert

**Status:** Implemented for TicketPal; proposed for provider-neutral v2.

**Current route:** `POST /api/ticketpal/performances/upsert`

**Purpose:** Create or update a scheduled occurrence of an existing provider show and resolve its Organisation-owned venue.

#### Request

| Field | Requirement | Validation/current behavior |
| --- | --- | --- |
| `provider_event_id` | Required | String, maximum 255; identifies the existing TicketPal show |
| `provider_performance_id` | Required | String, maximum 255; stable performance identity |
| `starts_at` | Required | Parseable date/time, normalized to UTC |
| `venue_name` | Required | String, maximum 255 |
| `ends_at` | Optional | Nullable date/time after `starts_at` |
| `status` | Optional | Nullable string, maximum 255 |
| `venue_city` | Optional | Nullable string, maximum 255 |
| `venue_postcode` | Optional | Nullable string, maximum 255 |

**Security:** Current TicketPal authentication and signed event headers are required.

**Idempotency:** Delivery idempotency uses the provider event store. Domain identity is `ticketpal + provider_performance_id`.

**Success:** HTTP 200 with `ok`, `created`, performance ID, show ID, venue ID, and status.

**Errors:** HTTP 422 when the show is absent, unassigned to an Organisation, or the performance ID belongs to another show. Authentication, replay, and server errors follow the common contract.

**Decision required for v2:** approve provider-neutral show identity, performance status vocabulary, date format, venue resolution rules, and out-of-order update policy.

### 7.3 Performance Completed

**Status:** Proposed capability; no route, controller, service, schema transition, or test currently exists.

**Purpose:** Notify Encore that a performance has reached an authoritative completed state.

| Contract element | Status |
| --- | --- |
| Route and versioned URI | Decision required |
| Required fields | Decision required; stable performance identity and completion evidence must be designed |
| Optional fields | Decision required |
| Validation | Decision required, including state transition and out-of-order delivery rules |
| Security | Proposed common v2 authentication |
| Idempotency | Proposed provider event identity plus an approved domain transition rule |
| Success response | Decision required |
| Error behavior | Common errors plus transition-specific errors to be approved |

Implementation MUST NOT begin until the owning capability, data ownership, state machine, payload, and acceptance criteria are approved in this specification and an ADR where architecturally material.

### 7.4 Ticket Scanned

**Status:** Proposed capability; no route, controller, service, scan entity, schema, or test currently exists.

**Purpose:** Provide attendance evidence from a ticket scan for a specific performance.

| Contract element | Status |
| --- | --- |
| Route and versioned URI | Decision required |
| Required fields | Decision required; ticket identity, performance identity, scan identity/time, and evidence semantics require privacy review |
| Optional fields | Decision required |
| Validation | Decision required, including duplicate, reversal, multi-scan, and unknown-ticket behavior |
| Security | Proposed common v2 authentication |
| Idempotency | Proposed provider event identity; ticket/scan domain uniqueness is not designed |
| Success response | Decision required |
| Error behavior | Common errors plus attendance-specific errors to be approved |

Ticket Scan design MUST define PII minimization, retention, lawful purpose, organisation ownership, fraud handling, reversal/correction, and whether Encore stores raw ticket identifiers. No such behavior is currently authorized.

## 8. HTTP status codes

| Status | Current TicketPal behavior | Proposed v2 meaning |
| --- | --- | --- |
| 200 | Successful show/performance create or update; may be a replay | Successful synchronous operation or replay of an original 200 |
| 201 | Successful invitation creation; may be a replay | Resource created where the endpoint contract explicitly uses 201 |
| 400 | No stable provider-specific guarantee documented | Malformed HTTP or JSON that cannot enter field validation |
| 401 | Missing/invalid legacy secret, event headers, signature, or stale timestamp | Missing/invalid credential, signature, or freshness proof |
| 403 | Not currently returned by provider routes as a defined contract | Authenticated provider lacks scope for the operation/resource |
| 409 | Event processing collision, payload conflict, exhausted failure, or unavailable/expired replay response | Same, plus approved domain-state conflicts |
| 422 | Laravel field validation or implemented domain validation failure | Syntactically valid request that violates field/domain rules |
| 429 | No provider-specific rate limiter currently implemented | Approved provider rate or quota exceeded; include `Retry-After` |
| 500 | Unhandled failure; event marked failed | Unexpected Encore failure; provider applies bounded retry policy |

Providers MUST NOT assume 400, 403, or 429 behavior until v2 is implemented and verified. Error response schemas for v2 remain a decision required.

## 9. Retry behavior

### 9.1 Safe retries

A provider MAY retry a request only with the same event ID, timestamp/signature regenerated as allowed by the accepted freshness contract, and byte-identical payload. The domain operation will not run twice for a processed event. A replay can return the original response while retained.

### 9.2 Unsafe retries

Providers MUST NOT:

- reuse an event ID for changed payload bytes;
- generate a new event ID merely because a response was lost, unless Encore has confirmed the original was never registered;
- retry permanent 401, 403, 409 payload-conflict, or 422 responses without corrective action;
- retry indefinitely;
- treat an expired replay response as permission to repeat business processing.

### 9.3 Backoff recommendation

For network failures, HTTP 500, and processing-in-progress 409 responses, use exponential backoff with jitter and a bounded attempt/time budget. Honor `Retry-After` when present. The current processing collision returns `Retry-After: 1`. Production retry limits and escalation ownership must be agreed in the ICD before launch.

## 10. Versioning and change management

The document version is v2; the implemented API remains unversioned TicketPal v1-equivalent behavior. v2 becomes the current contract only after formal approval, implementation, integration testing, and coordinated activation.

- Breaking changes require a new major API version and migration programme.
- Backward-compatible additive fields require documented optionality and consumer verification.
- Deprecation requires named owners, notice period, telemetry, migration instructions, rollback plan, and an agreed removal date.
- No provider contract change may be implemented before this specification is updated and approved.
- Architectural changes require an accepted ADR before implementation.

## 11. Security requirements

### Current controls

- TLS is an infrastructure requirement; application code does not enforce a particular TLS version.
- TicketPal uses one application-wide secret, shared-secret comparison, signed payloads, ±300-second freshness, event identity, payload hashing, encrypted replay responses, and bounded attempts.
- Raw provider payloads are not retained by the event store.

### Proposed v2 requirements

- TLS 1.2 or later, with TLS 1.3 preferred.
- Secrets generated with adequate entropy and stored only in approved secret-management systems.
- Per-provider, and where required per-integration, credential scope.
- Key IDs, revocation, documented rotation, and overlapping rotation without downtime.
- Secrets and signatures MUST NOT appear in URLs, payloads, application logs, audit snapshots, tickets, or chat systems.
- Payloads MUST minimize PII. Email, ticket, booking, attendance, and scan data require purpose and retention approval.
- Provider logs SHOULD record event ID, correlation ID, endpoint, outcome, and timing without raw secrets or unnecessary payloads.

## 12. Operational requirements

Providers and Encore must monitor request volume, authentication failures, latency, status distribution, retries, payload conflicts, processing failures, replay expiry, and clock skew. Every support case must use the correlation ID and provider event ID.

Current Encore stores integration-event lifecycle evidence but has no provider dashboard, centralized metrics, alerting, automated expired-response cleanup, or reconciliation workflow. These remain production risks, not implemented controls.

Administrative `audit_logs` do not record normal provider writes. The provider event store is the current operational evidence for provider deliveries. Any future requirement to create business audit records from provider events needs separate design.

## 13. Approval gates and open decisions

v2 implementation is blocked until all of the following are approved:

1. Versioned provider-neutral URI structure.
2. Provider credential model, key IDs, scope, revocation, and rotation.
3. Correlation-ID origin and collision rules.
4. Stable provider show identity distinct from delivery event identity.
5. Organisation assignment authority.
6. Standard error envelope and 400/403/429 behavior.
7. Performance status vocabulary and event ordering.
8. Performance Completed state machine and payload.
9. Ticket Scanned ownership, privacy, retention, correction, and payload.
10. Rate limits, payload limits, monitoring, retention, and support service levels.

Until those decisions are closed, the [current TicketPal API reference](README.md) is the only executable contract.
