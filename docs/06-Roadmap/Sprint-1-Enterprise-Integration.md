# Sprint 1: Enterprise Integration

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Planning only
- Baseline dependency: v0.3.0 Enterprise Foundation
- Implementation authority: None
- Owner: Encore Reviews Product and Engineering

## Objective

Sprint 1 planning identifies the next capability areas that may build on the Enterprise Foundation baseline. It does not approve architecture, API contracts, schemas, events, queues, services, or user experiences.

Each capability must complete the Strategic Review, Engineering Review, and Founder Approval stages defined in the [Operating Principles](../00-Vision/Operating-Principles.md) before implementation. Dependencies below identify governance or prerequisite decisions, not an implementation design.

## Capability placeholders

### Performance Completed

**Business objective:** Establish an authoritative understanding that a scheduled performance has completed so later review workflows can rely on its lifecycle state.

**Dependencies:** Organisation/performance ownership; provider contract approval; state ownership and correction policy; Provider API Specification update; privacy and retention assessment where applicable.

**Status:** Placeholder — not implemented.

**Architectural readiness:** Blocked. Route, payload, state transition, source authority, ordering, and idempotency decisions remain open.

### Invitation Engine

**Business objective:** Govern creation and lifecycle of review invitations consistently across approved sources and delivery channels.

**Dependencies:** Existing performance-level invitation model; eligibility policy; provider/source ownership; retention and privacy rules; duplicate and correction semantics.

**Status:** Placeholder — basic TicketPal invitation creation exists, broader capability not defined.

**Architectural readiness:** Requires review. Capability boundary and relationship to current synchronous invitation creation must be approved.

### Email Queue

**Business objective:** Deliver approved communications reliably without coupling request-critical workflows to external email availability.

**Dependencies:** Invitation Engine; email ownership and consent rules; accepted or superseding queue decision; worker operations, retries, failure handling, monitoring, and provider selection.

**Status:** Placeholder — no business email job is implemented.

**Architectural readiness:** Blocked while ADR-008 remains Proposed and production queue operations are undefined.

### Magic Links

**Business objective:** Provide a secure, bounded link-based path into an approved review or invitation workflow.

**Dependencies:** Invitation Engine; token purpose and lifecycle; identity/privacy review; abuse controls; expiry, revocation, and recovery policy; public API/security review.

**Status:** Placeholder — not implemented as a governed capability.

**Architectural readiness:** Blocked pending authentication, token, and public-contract decisions.

### Review Verification

**Business objective:** Determine and preserve the evidence supporting a review's verified status in a consistent, explainable manner.

**Dependencies:** Performance-level verification baseline; Invitation Engine; attendance evidence policy; provider data ownership; privacy, retention, and dispute rules.

**Status:** Placeholder — invitation-backed verification exists; broader verification capability is not defined.

**Architectural readiness:** Requires review. New verification sources or evidence models may affect domain and provider contracts.

### Review Publication

**Business objective:** Govern when approved review content becomes publicly visible and contributes to public aggregates.

**Dependencies:** Existing moderation baseline; publication eligibility policy; review lifecycle and correction rules; audit requirements; public API/cache implications.

**Status:** Placeholder — approved reviews are currently published by query behavior; a broader publication capability is not defined.

**Architectural readiness:** Requires review. Current behavior is synchronous/query-based and must not be described as an event-driven publication system.

### Ticket Redirect Analytics

**Business objective:** Measure approved outbound ticket-interest activity while preserving user privacy and trustworthy Organisation/show attribution.

**Dependencies:** Ticket URL ownership; analytics purpose and consent; data minimization and retention; public redirect contract; abuse controls; reporting ownership.

**Status:** Placeholder — not implemented.

**Architectural readiness:** Blocked pending privacy, public API, data ownership, and operational analytics decisions.

## Sprint 1 readiness gate

Before any placeholder enters implementation, its owner must provide:

- recorded Strategic Review, Engineering Review, and Founder Approval;
- an approved business scope and acceptance criteria;
- architecture review outcome;
- domain and data-ownership impact;
- security and privacy assessment;
- Provider API Specification update where applicable;
- ADR approval for architectural change;
- testing, operations, documentation, and rollback expectations.

Prioritization and sprint commitment occur only after these gates. Listing a capability here does not authorize work.
