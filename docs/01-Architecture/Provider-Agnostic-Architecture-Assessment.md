# Provider-Agnostic Architecture Assessment

**Version:** 1.0

**Date:** 24 July 2026

**Status:** Engineering assessment only — no architecture changes authorized

## Purpose

This report evaluates the current Encore architecture against the [Platform Strategy](../00-Vision/Platform-Strategy.md). It identifies existing provider-agnostic foundations, TicketPal-specific assumptions requiring containment or abstraction, a suggested provider interface design, and a staged roadmap for additional providers.

## Executive Assessment

Encore's core domain is already substantially provider-neutral: organisations, shows, performances, venues, reviewers, invitations, and reviews use Encore identities; provider identifiers are integration metadata; and the accepted ADRs establish provider-independent ownership.

The delivery boundary remains TicketPal-specific: routes, headers, authentication, controller namespaces, configuration, invitation defaults, tests, and current operational contracts assume one provider. This is an acceptable current implementation, but it is not yet a reusable multi-provider integration architecture.

The recommended approach is to preserve the current TicketPal adapter, introduce provider connection and capability concepts only when the second provider requirements are concrete, and route provider-neutral commands through stable application services. Do not build a speculative universal adapter framework.

## Areas Already Provider-Agnostic

| Area | Current strength |
| --- | --- |
| Organisation ownership | `Organisation` is the root domain and does not depend on TicketPal customer terminology |
| Encore identifiers | Core domain records use Encore-controlled UUIDs |
| Provider metadata | Shows and performances retain `provider_source` and external identifiers separately |
| Performance model | Reviews and invitations attach to Encore performances rather than provider bookings or tickets |
| Venue ownership | Venues are organisation-scoped Encore entities |
| Review trust | Review submission consumes an Encore invitation token rather than a provider session |
| Public product | Show and review pages are not authenticated through TicketPal |
| Administrative tenancy | Organisation policies are independent of provider identity |
| Synchronization principles | Idempotent upserts and provider event evidence are reusable architectural patterns |
| Proposed contract governance | Provider API v2, the Interface Control Document, and conformance plans use provider-neutral target language |
| ADR foundation | ADR-001, ADR-006, ADR-009, ADR-010, ADR-011, ADR-014, and ADR-015 protect stable Encore boundaries |

## TicketPal-Specific Assumptions Requiring Abstraction or Containment

### Route and Transport Boundary

- `/api/ticketpal/*` route namespace;
- `X-TicketPal-*` headers;
- TicketPal middleware aliases;
- TicketPal-specific controller namespaces;
- one synchronous JSON interaction model.

**Assessment:** Correctly contained but not reusable. Keep for compatibility while introducing a versioned provider-neutral contract.

### Authentication and Credential Scope

- one application-wide TicketPal secret;
- no provider connection identity;
- no organisation-scoped capability grant;
- no credential identifier or overlap rotation;
- provider name implied by route and middleware.

**Assessment:** Requires abstraction before a second provider or multiple independent provider connections can issue trusted evidence safely.

### Provider Event Registration

The event store supports a provider dimension conceptually, but current registration and middleware are designed around TicketPal headers and response replay.

**Assessment:** Reuse the event lifecycle while separating provider authentication, connection identity, capability scope, and transport mapping.

### Show and Performance Mapping

Current payloads and services use TicketPal routes and field expectations. Venue resolution and organisation assignment rules may not match other providers.

**Assessment:** Preserve provider-neutral application commands and move provider payload mapping to adapters. Do not force every provider into TicketPal's payload vocabulary.

### Invitation Issuance

The current endpoint defaults `provider_source` to `ticketpal` and creates authority-bearing invitations directly.

**Assessment:** This is the most important boundary to redesign. Providers should submit approved evidence or an eligibility command within their capability; Encore should apply authority policy.

### Provider Capability Discovery

The application does not represent whether a provider supports catalogue, performance, attendance, invitation, correction, reconciliation, or outcome capabilities.

**Assessment:** Add an explicit capability contract before graceful degradation can be product-safe.

### Operational Isolation

There is no per-provider queue capacity, circuit state, rate policy, reconciliation checkpoint, health view, or incident scope because only one provider exists.

**Assessment:** Introduce these controls incrementally when asynchronous processing and a second provider justify them.

### Tests and Fixtures

Feature tests use TicketPal-specific helpers and payloads. There is no shared provider conformance suite.

**Assessment:** Retain TicketPal tests and add provider-neutral contract tests when the second adapter exists.

## Terminology Review

Architectural documentation should use **Provider Integrations** when describing the general platform boundary, capability model, security pattern, or future ecosystem.

TicketPal-specific terminology remains correct when documenting:

- currently implemented routes and headers;
- current middleware and credentials;
- TicketPal migration and conformance work;
- TicketPal-specific operational runbooks;
- the flagship native integration experience;
- tests or code that are currently TicketPal-specific.

Replacing every TicketPal reference would make current-state documentation inaccurate. Provider-neutral strategy and TicketPal implementation truth must remain distinguishable.

## Suggested Provider Interface Design

The design should separate five responsibilities rather than create one large generic adapter.

### 1. Provider Connection

Represents one approved provider relationship and contains:

- provider key;
- organisation or permitted ownership scope;
- connection status;
- capability set;
- credential reference and rotation state;
- contract version;
- rate and operational policy;
- support ownership.

### 2. Provider Authenticator

Validates the transport-specific credential and produces an authenticated Provider Connection principal. It does not mutate domain records.

### 3. Provider Payload Mapper

Maps provider-specific payloads and vocabulary into versioned Encore commands or evidence submissions. It does not decide core authority, ownership, or publication policy.

### 4. Provider Capability Contract

Declares supported operations, evidence strength, correction behavior, and service limitations.

Suggested capability identifiers include:

- `catalogue.show.sync`;
- `catalogue.performance.sync`;
- `catalogue.venue.context`;
- `attendance.evidence.submit`;
- `review.authority.request`;
- `review.invitation.delivery`;
- `catalogue.reconcile`;
- `outcome.feedback`.

Capability names are illustrative and require contract approval.

### 5. Provider-Neutral Application Services

Core services accept authenticated connection context and stable Encore commands such as:

- synchronize show;
- synchronize performance;
- submit attendance evidence;
- evaluate review eligibility;
- issue or deliver a Verified Review Invitation;
- reconcile provider state.

These services enforce organisation scope, identity invariants, verification policy, idempotency, and audit requirements. Adapters must not bypass them.

## Interaction Model

```text
Provider Request
    ↓
Transport-Specific Authenticator
    ↓
Authenticated Provider Connection + Capability Check
    ↓
Provider Payload Mapper
    ↓
Provider-Neutral Encore Command / Evidence
    ↓
Application Service and Domain Policy
    ↓
Encore State + Audit/Event Evidence
    ↓
Provider-Specific Response Mapper
```

## Authority Boundary

Provider integration design must follow ADR-015:

- a provider authenticates as a connection principal;
- the provider may submit evidence only within its granted capabilities and scope;
- Encore evaluates evidence under approved verification policy;
- Encore grants contribution authority;
- a provider cannot grant review authority merely by knowing an audience identity;
- every review retains Encore authority provenance.

## Graceful Degradation Model

| Provider capability | Full native experience | Safe degraded experience |
| --- | --- | --- |
| Catalogue sync | Timely automatic updates | Approved manual or scheduled catalogue maintenance |
| Performance sync | Accurate occurrence data | Limited discovery until required performance data exists |
| Attendance evidence | Automated verified authority | No verified invitation unless an alternative approved evidence path exists |
| Invitation delivery | Native automated delivery | Encore-managed delivery after valid authority exists |
| Reconciliation | Automated drift repair | Manual support reconciliation with clear freshness limits |
| Outcome feedback | Measured ticket/attendance outcomes | No effectiveness claim beyond available evidence |

Degradation must never turn missing evidence into a weaker but identically labelled “verified” state.

## Suggested Roadmap for Additional Providers

### Stage 0 — Approve Product and Trust Contracts

- approve the provider capability model;
- define attendance evidence and review authority policy;
- approve ownership, correction, privacy, and support responsibilities;
- confirm the second provider's actual requirements.

### Stage 1 — Contain and Stabilize TicketPal

- retain existing routes as a compatibility adapter;
- move reusable business rules behind application services when those workflows change;
- establish conformance, observability, credential-rotation, and reconciliation evidence;
- document TicketPal's flagship capability profile.

### Stage 2 — Introduce Provider Connection and Scope

- represent provider connection identity and capabilities;
- add scoped, revocable credentials;
- prevent catalogue permission from implying attendance or authority permission;
- retain current TicketPal compatibility during migration.

### Stage 3 — Approve Provider-Neutral Contract v2

- finalize versioning, authentication, errors, idempotency, correlation, batching, and deprecation;
- map TicketPal through the approved contract or adapter;
- publish a machine-readable contract after decisions are accepted.

### Stage 4 — Implement Authority Provenance

- model approved attendance evidence and contribution authority;
- connect invitation and review provenance;
- define correction, revocation, and degraded verification behavior;
- prove analytics can retain verification classification.

### Stage 5 — Add One Second Provider Vertical Slice

- choose a provider based on customer demand and learning value;
- implement the minimum useful capability set end to end;
- run shared conformance, security, replay, authority, and reconciliation tests;
- compare activation, data quality, support cost, and user value with TicketPal.

### Stage 6 — Generalize Only from Evidence

- extract repeated adapter patterns demonstrated by two providers;
- add provider-specific queue and incident isolation when measured need exists;
- publish onboarding standards and capability matrix;
- defer unsupported generic extensibility.

## Engineering Risks

- premature generic abstractions modeled on TicketPal rather than real provider variation;
- provider capability flags becoming untyped metadata that bypasses invariants;
- inconsistent evidence causing false verification parity;
- credential scope allowing cross-organisation or excess capability access;
- provider delivery order and correction semantics corrupting authority;
- analytics combining incomparable provider coverage;
- operational cost growing faster than provider revenue or organisation value;
- TicketPal compatibility slowing the clean provider-neutral contract;
- native advantage being implemented as hidden exclusivity rather than quality.

## Engineering Recommendation

Do not replace the current TicketPal implementation pre-emptively.

First approve the provider capability and authority contracts, then introduce a scoped Provider Connection boundary and one provider-neutral application path. Use TicketPal as the flagship compatibility adapter and validate the design through one demand-led second provider. Generalize only where two real integrations demonstrate a stable common need.
