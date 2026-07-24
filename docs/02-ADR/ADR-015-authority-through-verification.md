# ADR-015: Authority Through Verification

- Status: Accepted
- Date: 2026-07-24
- Scope: Identity, access, verified contribution, audience-generated content, and AI provenance
- Extends: [ADR-009 — Anchor Verification at Performance Level](ADR-009-anchor-verification-at-performance-level.md)

## Context

Encore supports open public discovery, optional audience membership, and trusted audience contribution.

Identity and authority answer different questions:

- identity establishes who a person is and which personal capabilities they may access;
- authority establishes what trusted contribution they are permitted to make and the evidence supporting that permission.

If account ownership implicitly grants review permission, unverified contributions would weaken review integrity and every score, insight, recommendation, benchmark, and AI output derived from those contributions.

[ADR-009](ADR-009-anchor-verification-at-performance-level.md) establishes that review invitations and reviews attach to a specific performance. This decision extends that architecture into a broader platform principle without restating or replacing ADR-009's technical anchoring decision.

## Decision

Identity and authority are separate domain concepts.

- Identity grants access.
- Verification grants authority.
- Authority is a distinct domain concept from identity.
- Verified attendance authorises contribution.
- All trusted audience-generated content must derive its authority from explicit verification rather than account ownership.
- Future contribution features must define their verification mechanism before implementation.

For reviews, explicit authority is represented by a valid Verified Review Invitation whose provenance is anchored to verified attendance for a specific performance under ADR-009.

Account creation, authentication, profile completion, organisation membership, ticket purchase, and possession of an external provider identity are not sufficient on their own to grant trusted contribution authority.

## Consequences

- Reviews require Verified Review Invitations.
- Identity alone is insufficient.
- Future contribution features must define their verification model.
- AI trust depends upon verified data provenance.
- Public discovery and reading remain available without contribution authority.
- My Encore membership features remain separate from review eligibility.
- Authority must be scoped to a contribution type and subject, and must have an explicit lifecycle.
- Linking a verified contribution to an account must preserve its original authority provenance.
- Revoking account access does not silently rewrite historic verification evidence; retention and erasure policy determines the treatment of associated data.
- Providers may supply approved evidence, but Encore applies the verification policy and grants authority.
- Analytics, recommendations, and AI outputs must be able to distinguish verified contributions from any future non-authoritative audience signals.

## Product and Domain Implications

### Identity

Identity may enable:

- authentication;
- personal preferences;
- favourites, follows, and watchlists;
- personal history;
- notifications;
- organisation or platform administration where separately authorized.

Identity must not contain a permanent generic `can_review` entitlement that bypasses verification.

### Authority

Authority must identify:

- the contribution type;
- the verified subject or scope;
- the approved evidence source;
- the issue and lifecycle state;
- expiry, consumption, revocation, or correction where applicable;
- the contribution created under that authority.

### Future Contributions

Before a new trusted audience-generated contribution is implemented, its product and architecture design must answer:

1. What is the participant authorized to contribute?
2. What objective or approved evidence grants that authority?
3. What is the scope and lifetime of the authority?
4. Can it be used more than once?
5. How is it corrected, revoked, disputed, or audited?
6. How is provenance preserved in downstream intelligence and AI?

## Security and Privacy Implications

- Authentication and authorization checks must not be treated as verification evidence unless an explicit policy says why.
- Invitation or authority evidence must be protected against guessing, replay, reuse, transfer, and cross-performance misuse.
- Provider assertions must be authenticated, scoped, attributable, and correctable.
- Authority evidence should disclose the minimum audience information required for its approved purpose.
- Logs, analytics, exports, and AI pipelines must not expose raw authority tokens or unnecessary identity data.
- Account, consent, communication, and authority lifecycles must remain independently enforceable.

## Alternatives Considered

### Account membership grants review permission

Rejected because account identity does not prove attendance and would weaken the defining trust promise.

### Ticket purchase grants review permission automatically

Rejected as a universal rule because purchase does not necessarily prove attendance, a purchaser may buy for someone else, and provider evidence varies.

### Organisation administrators grant manual reviewer roles

Rejected as a general authority model because it creates inconsistent verification and potential conflicts of interest. Explicit exceptional verification processes may be considered only through approved policy and recorded evidence.

### Provider identity directly grants authority

Rejected because providers supply evidence but do not own Encore's authority model. Encore must apply consistent verification policy across providers.

## Required Verification

Changes affecting audience identity, account linking, review eligibility, contribution authorization, invitations, attendance evidence, analytics provenance, or AI training inputs must demonstrate compliance with this ADR and ADR-009.

## Related Strategic Foundation

- [The Encore Platform Manifesto](../00-Vision/The-Encore-Platform-Manifesto.md)
- [Encore Product Blueprint](../00-Vision/Encore-Product-Blueprint.md)
- [Audience Journey](../00-Vision/Audience-Journey.md)
- [Operating Principles](../00-Vision/Operating-Principles.md)
