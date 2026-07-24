# Authority Principle Product Guardian Review

**Version:** 1.0

**Date:** 24 July 2026

**Status:** Completed strategic consistency review

## Purpose

This review evaluates whether the Authority Principle is reflected consistently across the permanent strategic foundation and whether the resulting product direction protects Encore's long-term integrity.

The principle under review is:

> **Access is granted by identity. Authority is granted by verification.**

The defining brand statement is:

> **At Encore, anyone can discover. Only people who were there can contribute.**

## Documents Reviewed

- [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md)
- [Platform Strategy](Platform-Strategy.md)
- [Encore Product Blueprint](Encore-Product-Blueprint.md)
- [Audience Journey](Audience-Journey.md)
- [Operating Principles](Operating-Principles.md)
- [Encore Reviews Platform Charter](Encore-Platform-Charter.md)
- [ADR-009: Anchor Verification at Performance Level](../02-ADR/ADR-009-anchor-verification-at-performance-level.md)
- [ADR-015: Authority Through Verification](../02-ADR/ADR-015-authority-through-verification.md)
- Current architecture, domain, security, API, roadmap, and implementation-plan documentation

## Rating Scale

- **✓ Supports the principle** — states or enforces the intended separation clearly.
- **⚠ Partially supports the principle** — directionally aligned but incomplete or dependent on an unresolved policy.
- **✗ Conflicts with the principle** — permits identity or another insufficient signal to replace explicit verified authority.

## Executive Decision

**Strategic alignment: ✓ Approved.**

The authoritative vision documents now express a coherent product philosophy:

- public discovery is open;
- account membership unlocks personal value but not contribution rights;
- attendance evidence is required before review authority is granted;
- the Verified Review Invitation represents bounded contribution authority;
- account creation after contribution is optional;
- provider evidence remains distinct from Encore's authority decision;
- intelligence and AI inherit trust from verified provenance.

**Implementation alignment: ⚠ Partial.**

The current application separates administrative users, reviewers, and invitations, and requires a valid invitation token to submit a review. However, invitation issuance does not currently require explicit attendance evidence, and a review does not retain a direct relationship to the invitation that authorized it. These are engineering gaps, not reasons to weaken the strategic principle.

## Strategic Documentation Assessment

| Area | Rating | Finding |
| --- | :---: | --- |
| Manifesto | ✓ | Defines the Authority Principle and brand promise under trust |
| Platform Strategy | ✓ | Providers supply evidence; Encore owns authority and verification policy |
| Product Blueprint | ✓ | Separates membership from authority and requires future contribution verification models |
| Audience Journey | ✓ | Places verified attendance and invitation before review; account creation is optional afterward |
| Operating Principles | ✓ | Makes Authority Through Verification a future product and engineering decision test |
| Platform Charter | ✓ | Separates identity from contribution authority and retains performance-level verification |
| ADR-009 | ✓ | Anchors invitation and review provenance at the performance level |
| ADR-015 | ✓ | Extends ADR-009 into the broader identity-versus-authority platform rule |
| Domain documentation | ✓ | Distinguishes `User`, `Reviewer`, `ReviewInvitation`, and `Review` responsibilities |
| Security documentation | ✓ | Distinguishes authentication, administrative access, and invitation authority |
| API documentation | ✓ | States that review submission requires invitation authority and not an Encore account |
| Roadmap | ✓ | Adds the identity/authority distinction as a planning principle and capability constraint |
| Current implementation | ⚠ | Invitation use is enforced, but verified attendance is not required at invitation issuance |
| AI and intelligence strategy | ✓ | Requires verified provenance and prohibits AI from manufacturing authority or evidence |

## Product Integrity Tests

### Does this strengthen trust?

**Yes.** It makes the source of contribution authority understandable and prevents account growth from diluting review credibility.

### Does this improve discovery?

**Yes, indirectly.** Public discovery remains open while review evidence becomes more trustworthy. Account creation is not used as a discovery gate.

### Does this generate meaningful audience intelligence?

**Yes.** It defines the minimum provenance required for trusted review-derived intelligence.

### Does this help someone make a better decision?

**Yes.** Audiences can understand why reviews are trusted; organisations can understand the basis of their insight; engineers can evaluate future contribution models consistently.

### Does this support the Audience Journey?

**Yes.** The journey explicitly separates Discover, Book, Attend, Attendance Verified, Invitation, Review, and optional membership.

### Does this align with the Platform Manifesto?

**Yes.** It operationalizes the Manifesto's trust, privacy, intelligence, and audience-value commitments.

### Does this move Encore closer to becoming the Audience Intelligence Platform for Live Entertainment?

**Yes.** It creates a durable trust foundation for the audience intelligence claim rather than allowing intelligence volume to grow ahead of provenance.

## Product Risks to Guard

### Account Conversion Pressure

The desire to grow My Encore membership must not move account creation before review submission or make contribution conditional on marketing or personalisation consent.

### Provider Parity Pressure

The desire to support more providers must not label weak booking or identity data as verified attendance merely to present equal capability.

### Organisation Convenience

Organisation administrators must not receive a generic ability to grant review permission without an approved evidence and audit model.

### Contribution Expansion

Comments, questions, photographs, reactions, corrections, community posts, or other future contributions must not inherit review authority automatically. Each trusted contribution type requires its own verification policy.

### AI and Analytics Convenience

AI training and organisation dashboards must not silently combine authoritative verified reviews with unverified signals as though they have equal provenance.

### Historical Provenance

Linking or unlinking an audience account must not rewrite the historic authority under which a contribution was made.

## Product Guardian Conditions

Before implementing audience accounts or additional contribution features:

1. Define the identity, access, consent, and authority relationship explicitly.
2. Preserve guest use of a valid Verified Review Invitation.
3. Define how an optional account links to an existing contribution without changing provenance.
4. Define provider evidence levels and graceful degradation.
5. Show audiences why a contribution is considered verified.
6. Define correction, expiry, revocation, dispute, and appeal behavior.
7. Ensure analytics and AI can retain and filter by authority provenance.
8. Add Product Guardian acceptance criteria to the relevant roadmap capability.

## Product Guardian Decision

The Authority Principle is approved as part of Encore's permanent strategic foundation.

The vision is internally coherent on this principle. Implementation should not proceed until the critical attendance-evidence and authority-provenance gaps in the [Engineering Architecture Assessment](../01-Architecture/Authority-Principle-Engineering-Assessment.md) are resolved through approved design and ADRs where required.

Documentation inconsistencies and residual ambiguities are recorded separately in [Authority Principle Documentation Inconsistencies](Authority-Principle-Documentation-Inconsistencies.md).
