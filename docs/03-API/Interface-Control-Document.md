# Provider Interface Control Document

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Document status: Draft integration agreement
- Current interface: TicketPal-specific contract
- Target interface: Proposed Provider API v2
- Technical authority: [Provider API Specification v2](Provider-API-Specification-v2.md)
- Owner: Encore Reviews Engineering
- Last reviewed: 15 July 2026

## Document control and approval

| Field | Value |
| --- | --- |
| Provider | TicketPal for the first completed instance |
| Approved contract version | Current TicketPal contract; proposed v2 not approved |
| Effective date | TBD |
| Review/expiry date | TBD |
| Encore technical approver | TBD |
| Encore security/data approver | TBD |
| Provider technical approver | TBD |
| Commercial/data agreement reference | TBD |

An ICD is not active until the provider-specific TBD fields, service parameters, environment details, and approvals are completed.

## 1. Purpose and precedence

This Interface Control Document (ICD) assigns responsibilities and operating rules for data exchanged between Encore Reviews and an external provider. It is intended to be completed and approved for each provider onboarding.

Provider relationships must follow the [Operating Principles](../00-Vision/Operating-Principles.md) and [ADR-000](../01-Architecture/ADR-000-Founding-Principles.md): each product retains standalone value, integrations should strengthen the ecosystem, and Encore remains provider-agnostic.

If documents conflict, precedence is:

1. An executed commercial/data-processing agreement for legal obligations.
2. The approved Provider API Specification for technical contract behavior.
3. This provider-specific ICD for responsibilities, environments, and operation.
4. Implementation checklists and test plans.

The current TicketPal implementation remains governed by the [HTTP API Reference](README.md) until Provider API v2 is approved and activated.

## 2. Parties and interface boundary

| Role | Party | Responsibility |
| --- | --- | --- |
| Sender | External provider; currently TicketPal | Construct, authenticate, transmit, retain delivery identity, and interpret responses |
| Receiver | Encore Reviews | Authenticate, register, validate, process idempotently, respond, and retain bounded operational evidence |
| Data owner | Defined per data class below | Determines authoritative source and correction process |
| Contract owner | Encore Reviews Engineering with provider approval | Maintains specification, version, and change record |

Encore is an independent review platform. Providers are integration parties, not owners of Encore's domain language. `Organisation` remains the root owner of Encore customer data.

## 3. Interface inventory

### 3.1 Current TicketPal interface

| Operation | Sender | Receiver | Transport | Current endpoint |
| --- | --- | --- | --- | --- |
| Show upsert | TicketPal | Encore | HTTPS JSON POST | `/api/ticketpal/shows/upsert` |
| Performance upsert | TicketPal | Encore | HTTPS JSON POST | `/api/ticketpal/performances/upsert` |
| Review invitation creation | TicketPal | Encore | HTTPS JSON POST | `/api/ticketpal/invitations` |

### 3.2 Proposed v2 interface

| Operation | Status | Activation condition |
| --- | --- | --- |
| Provider-neutral show upsert | Proposed | v2 route, identity, scope, and payload approved |
| Provider-neutral performance upsert | Proposed | v2 route, identity, status, and ordering approved |
| Performance Completed | Not implemented | State machine and payload approved |
| Ticket Scanned | Not implemented | Ownership, privacy, lifecycle, and payload approved |

No row in the proposed inventory authorizes implementation.

## 4. Transport and encoding

### Current

- HTTP POST with `Content-Type: application/json`.
- UTF-8 JSON payloads.
- TLS termination is a deployment responsibility; the repository does not enforce a TLS version.
- Responses are JSON for implemented endpoints and middleware errors.

### Proposed v2

- HTTPS only, TLS 1.2 minimum and TLS 1.3 preferred.
- UTF-8 JSON with the exact transmitted bytes used for signature verification.
- Maximum payload size, connection timeout, and response timeout: decision required before production approval.
- Compression behavior: decision required because signing must operate on an unambiguous representation.

## 5. Authentication and headers

### 5.1 Current TicketPal request headers

| Header | Responsibility | Requirement |
| --- | --- | --- |
| `X-TicketPal-Secret` | TicketPal sends; Encore validates | Required |
| `X-TicketPal-Event-ID` | TicketPal generates and persists | Required; unique and immutable |
| `X-TicketPal-Timestamp` | TicketPal generates immediately before sending | Required; Unix seconds within configured ±300-second window |
| `X-TicketPal-Signature` | TicketPal computes over exact raw body | Required; HMAC-SHA256 |

Encore generates `X-Correlation-ID` after a request is registered. It is not currently accepted as the authoritative inbound correlation ID.

### 5.2 Proposed v2 headers

`X-Provider`, `X-Provider-Event-ID`, `X-Provider-Timestamp`, `X-Provider-Signature`, and `X-Correlation-ID` are proposed as required. Their normative definitions are in the Provider API Specification. Credential lookup and rotation remain approval gates.

### 5.3 Secret responsibilities

| Provider responsibility | Encore responsibility |
| --- | --- |
| Restrict secret access to the sending workload and authorized operators | Store secrets outside source control and restrict application/operator access |
| Never log, email, or place secrets in URLs | Never return or log provider secrets/signature material |
| Notify Encore immediately of suspected compromise | Revoke/replace compromised material and coordinate incident response |
| Participate in rotation and prove both ends before cutover | Define rotation window, activation, rollback, and removal evidence |

Current TicketPal supports one shared secret without a key ID or overlapping rotation mechanism. This limitation must be acknowledged in the provider launch record.

## 6. Payload and data ownership

| Data class | Proposed authoritative source | Encore responsibility | Provider responsibility |
| --- | --- | --- | --- |
| Provider show identity and supplied show metadata | Provider, subject to agreed field ownership | Validate and map without replacing Organisation ownership | Supply stable identifiers and corrections |
| Encore Organisation assignment | Encore unless explicitly delegated | Prevent cross-tenant assignment | Must not infer or alter ownership without authorization |
| Provider performance identity and schedule | Provider | Preserve stable mapping and validate show ownership | Supply stable ID, timestamps, and corrections |
| Venue mapping | Shared mapping; Encore owns stored Venue | Resolve within Organisation and prevent cross-tenant collisions | Supply consistent venue source data |
| Review invitation evidence | Provider supplies eligibility inputs; Encore owns invitation/token lifecycle | Minimize identity data, issue single-use proof, protect token | Supply lawful and accurate booking/ticket evidence |
| Reviews and moderation | Encore | Own review, verification, moderation, and publication lifecycle | No direct ownership or mutation authority |
| Performance completion | Decision required | Not implemented | Authority and correction process undefined |
| Ticket scan/attendance | Decision required | Not implemented | Authority, privacy basis, and correction process undefined |
| Provider delivery record | Encore operational evidence | Retain hash/lifecycle/correlation under policy | Retain source event ID and delivery history |

Raw request payloads are not retained in Encore's provider event store. This does not remove the need for a data-processing and retention agreement for values persisted by domain workflows.

## 7. Processing and idempotency responsibilities

### Sender

- Generate one immutable event ID for one logical delivery.
- Persist the event ID before transmission.
- Reuse the same event ID and byte-identical payload after ambiguous transport failure.
- Never reuse an event ID for amended data.
- Keep enough delivery history to reconcile with Encore using event and correlation IDs.

### Receiver

- Authenticate and freshness-check before registration.
- Register `provider + external event ID` before business processing.
- Verify the stored payload hash on duplicates.
- Prevent duplicate domain processing.
- Return the original encrypted response while retained.
- Bound failure attempts and avoid exposing internal exception details.

## 8. Responses and errors

The current status contract and proposed v2 meanings are defined in the Provider API Specification. The provider must classify responses as follows:

| Class | Provider action |
| --- | --- |
| 2xx | Record success and response correlation ID; do not send a new event for the same operation |
| 401 | Stop; verify credentials, signature bytes, timestamp, and clock before retrying |
| 403 | Proposed v2 only; stop and escalate scope/ownership authorization |
| 409 processing | Honor `Retry-After`; retry the identical event within the agreed budget |
| 409 payload conflict/exhausted/expired replay | Stop automated retry and reconcile with Encore |
| 422 | Correct the data or mapping; changed payload requires a new event ID only after the original outcome is understood |
| 429 | Proposed v2 only; honor `Retry-After` and back off |
| 500/network timeout | Retry identical event with exponential backoff and jitter; escalate after budget |

The v2 machine-readable error envelope remains a decision required. Providers MUST NOT parse free-text messages as stable identifiers.

## 9. Retry and recovery agreement

Before production activation, the parties must fill and approve:

| Parameter | Agreed value |
| --- | --- |
| Initial retry delay | TBD |
| Backoff multiplier and jitter | TBD |
| Maximum automated attempts | TBD; Encore currently permits three failed processing attempts |
| Maximum retry age | TBD; must account for signature freshness and replay retention |
| Operational escalation time | TBD |
| Reconciliation owner | TBD |
| Provider event retention | TBD |
| Encore event/response retention | Current response default seven days; long-term event policy TBD |

Recovery MUST preserve the original event ID and payload unless both parties determine that a corrected logical event is required.

## 10. Observability and audit

Both parties must be able to search by provider event ID, correlation ID, endpoint/operation, time, and outcome. Logs must exclude secrets, signatures, raw invitation tokens, unnecessary email addresses, and unnecessary ticket identifiers.

Encore's current `integration_events` record is delivery evidence under [ADR-014](../02-ADR/ADR-014-provider-event-store.md). Normal provider writes do not create `audit_logs`; those records cover privileged human administrative activity under [ADR-012](../02-ADR/ADR-012-transactional-administrative-audit-logging.md). A future provider business-audit requirement needs a separate approved design.

Monitoring thresholds, dashboards, alert routes, log retention, and incident contacts are TBD and must be completed before production v2 activation.

## 11. Environment and release responsibilities

| Stage | Provider responsibility | Encore responsibility | Joint exit evidence |
| --- | --- | --- | --- |
| Development | Implement canonical signing and deterministic event IDs | Publish fixtures/specification and test endpoint behavior | Shared signature vectors pass |
| Test/staging | Exercise complete retry/replay matrix | Provide representative environment and event inspection | End-to-end test plan signed off |
| Production preparation | Deploy disabled/configurable signing and monitoring | Deploy compatible receiver and monitoring | Change/rollback plans approved |
| Production | Send only approved contract and retain delivery evidence | Process only approved contract and retain event evidence | Health, auth, replay, and correlation checks pass |

## 12. Version ownership and change management

Encore Reviews Engineering maintains the canonical Provider API Specification. A provider-specific change request must identify affected fields, security controls, compatibility, data ownership, test evidence, rollout, and rollback.

No party may rely on an undocumented behavior. No integration-contract code change may begin until the specification is updated and approved. Architectural changes require an ADR before implementation. Breaking changes require a new major version or an explicitly approved migration window.

Each production provider ICD must record:

- provider technical owner and 24/7 incident contact where required;
- Encore technical owner and incident contact;
- environments and base URLs;
- credential exchange and rotation procedure;
- data-processing agreement reference;
- service levels, maintenance windows, and escalation path;
- approved contract version and activation/removal dates.

## 13. Current open items for TicketPal

1. Confirm canonical signing implementation against shared test vectors.
2. Confirm stable event ID generation and retention.
3. Coordinate the breaking requirement for signed TicketPal headers.
4. Decide whether a dual-authentication migration phase is acceptable; it is not currently implemented.
5. Agree retry budget, reconciliation ownership, and response-retention implications.
6. Complete staging and production validation using the end-to-end test plan.
7. Do not schedule Performance Completed or Ticket Scanned until their contracts and ownership decisions are approved.
