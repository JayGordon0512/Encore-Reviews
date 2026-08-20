# Authority Principle Engineering Architecture Assessment

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 1.0

**Date:** 24 July 2026

**Status:** Recommendations only — no code changes authorized

## Purpose

This assessment reviews the current Encore domain model and architecture against [ADR-015: Authority Through Verification](../02-ADR/ADR-015-authority-through-verification.md).

It identifies where identity and authority are already separated, where they remain coupled or incomplete, and what architectural changes should be considered before implementation. It does not prescribe final schemas or authorize application changes.

## Executive Assessment

The current review-submission workflow already has a valuable separation:

- Laravel `User` represents administrative identity and access;
- `Reviewer` represents a pseudonymized review author identity;
- `ReviewInvitation` supplies a token used to authorize one review;
- `Review` attaches to the invitation's `Performance`;
- review submission does not require an authenticated Encore account;
- row locking and `used_at` prevent effective invitation reuse.

However, the workflow currently proves possession of a valid invitation, not verified attendance itself. The TicketPal invitation endpoint can issue an invitation without `attendance_state`, including for a future scheduled performance. The review also does not retain a direct foreign-key relationship to the invitation or authority that authorized it.

**Engineering conclusion:** Identity and invitation use are substantially separated, but verified attendance and durable authority provenance are not yet first-class enforced domain concepts.

## Areas Already Aligned

### Administrative Identity Is Separate

The authenticated `User` model is used for organisation and Encore administration. Public review submission does not authenticate through this model and therefore does not inherit review permission from administrative identity.

### Reviewer Identity Is Separate from Invitation Token

`Reviewer` stores a pseudonymous email hash and optional display name. `ReviewInvitation` independently stores its token hash, intended email hash, performance, expiry, and use state.

### Review Submission Requires Invitation Possession

`POST /api/reviews` requires an invitation token. The controller hashes the token and resolves an unused, unexpired invitation.

### Intended Identity Can Be Checked

When the invitation has an email hash, review submission requires a matching normalized email hash. Possession and intended identity are therefore both checked where configured.

### Authority Is Bounded to a Performance

The created review uses the invitation's `performance_id`, supporting ADR-009 and preventing the submitter from selecting another performance.

### Authority Is Effectively Single-Use

The invitation row is locked inside the review transaction and marked used after review creation. Concurrent use cannot normally create two committed reviews from one invitation.

### Account Creation Is Not Required

The public review API and page have no audience-account dependency. This supports the approved Audience Journey.

## Critical Gaps

### 1. Invitation Issuance Does Not Require Verified Attendance

**Current state:** The TicketPal invitation endpoint accepts a performance and email. `attendance_state` is optional and free-form. The endpoint can create an invitation for a future scheduled performance. Existing tests exercise this path.

**Authority risk:** The platform grants effective review authority because an authenticated provider requested an invitation, not because the domain has established verified attendance.

**Recommendation:** Define and enforce an approved attendance-evidence policy before an invitation becomes a Verified Review Invitation. Model evidence source, subject, performance, state, observed time, issuer, correction, and confidence as required by product policy.

**Decision required:** Whether provider assertion is itself accepted evidence for specific provider capabilities, or whether completed/scanned/other evidence is mandatory.

### 2. Review Does Not Retain Direct Authority Provenance

**Current state:** `Review` stores `performance_id`, `verified`, and `verification_source = invitation`, but no `review_invitation_id` or authority identifier. After submission, the exact invitation can be inferred only indirectly and may be ambiguous.

**Authority risk:** Analytics, support, correction, audit, and AI pipelines cannot reliably trace a review to the specific evidence and authority record that created it.

**Recommendation:** Add an immutable relationship from a trusted contribution to the authority used. For the current workflow, evaluate a unique review-to-invitation reference. If a broader authority aggregate is approved, link the review to that aggregate and retain invitation delivery separately.

### 3. Verification Is Represented by Mutable-Looking Flags

**Current state:** `Review.verified` and `verification_source` are fillable fields set by controller convention. Database constraints do not prove that a verified review has a valid authority record.

**Authority risk:** Future code paths, imports, administrative tools, or tests can create `verified = true` reviews without explicit provenance.

**Recommendation:** Make verification a derived or constrained consequence of authority provenance rather than a freely assignable assertion. Define migration and compatibility behavior before changing current records.

### 4. Authority Token Integrity Is Not Database-Enforced

**Current state:** `review_invitations.token_hash` is nullable and has no visible unique constraint in the current migration. Application generation makes collision unlikely but does not enforce the invariant.

**Authority risk:** Null or duplicate token hashes undermine deterministic authority lookup and recovery assumptions.

**Recommendation:** Validate existing data, then require a non-null unique token hash for active token-based authority. If other invitation types are introduced, use explicit type-specific constraints rather than nullable ambiguity.

## High-Priority Design Gaps

### 5. Invitation Conflates Eligibility, Authority, and Delivery

`ReviewInvitation` currently carries performance, intended identity, token, provider booking/ticket fields, attendance state, delivery time, expiry, and use state.

**Recommendation:** Evaluate three explicit concepts:

- **Attendance Evidence:** what the approved source observed;
- **Contribution Authority or Review Eligibility:** what Encore permits under policy;
- **Invitation Delivery:** how the audience member receives and exercises that authority.

Keep them combined only if the simpler model can preserve correction, multiple delivery attempts, evidence provenance, and future provider capability safely.

### 6. Reviewer Identity Uniqueness Is Weak

`reviewers.email_hash` is nullable and indexed but not uniquely constrained. `firstOrCreate` may race, and a future audience account could be linked ambiguously.

**Recommendation:** Define the audience identity strategy before adding accounts. Use explicit uniqueness, keyed/versioned pseudonymous identifiers, and merge/split/link workflows appropriate to privacy requirements. Do not make `Reviewer` automatically equivalent to `AudienceAccount`.

### 7. Provider Capability Does Not Bound Authority Issuance

The current TicketPal route uses one application-wide secret and can create invitations for any existing performance identifier. Provider identity and organisation scope are not represented as a first-class connection capability.

**Recommendation:** Introduce provider connection scope and explicit capabilities before multi-provider authority issuance. A credential permitted to synchronize catalogue data must not automatically be permitted to assert attendance or grant review eligibility.

### 8. Correction and Revocation Are Undefined

Invitation expiry and use are represented, but attendance evidence correction, authority revocation, incorrectly issued invitations, and the treatment of already-submitted reviews are not defined.

**Recommendation:** Approve lifecycle rules for evidence correction, authority cancellation, contribution retention, reclassification, audience notice, organisation insight, and AI-derived outputs.

### 9. Authority Logic Is Controller-Led

Review submission validation, reviewer resolution, review creation, verification flags, and invitation consumption occur in the HTTP controller.

**Recommendation:** Before extending the workflow, move the transaction and authority invariant into a focused application service. The controller should remain a delivery boundary. This extends the existing service-layer direction without requiring a new deployable service.

## Medium-Priority Gaps

### 10. Verification Vocabulary Is Free-Form

`attendance_state`, `verification_source`, and several lifecycle values are strings without an authoritative closed vocabulary.

**Recommendation:** Define product-level evidence and authority states first, then constrain them through appropriate application and database rules.

### 11. Public Verification Explanation Is Limited

The public product can show that a review is verified, but the documentation does not define user-facing levels or explanations for different provider evidence.

**Recommendation:** Create a verification presentation standard that explains provenance accurately without exposing personal or provider-sensitive data.

### 12. Tests Prove Token Use but Not Attendance Authority

Existing tests cover valid token submission, reuse rejection, expiry behavior, and email mismatch. They also demonstrate that an invitation may be created without attendance evidence.

**Recommendation:** Future tests should cover evidence acceptance, future-performance rejection where policy requires it, provider capability scope, correction, revocation, direct authority provenance, account-independent review, and negative attempts to create verified content without authority.

### 13. AI and Analytics Provenance Are Not Yet Implemented

Current analytics are limited, and no AI pipeline exists. This avoids immediate coupling but creates a future design requirement.

**Recommendation:** Require all derived datasets and models to retain verification/authority classification, source version, inclusion rules, and correction/deletion propagation before they influence customer insight.

## Identity and Authority Target Relationship

The recommended conceptual relationship is:

```text
Audience Identity (optional account or pseudonymous identity)
        │
        │ may be intended recipient / contributor
        ▼
Attendance Evidence ── evaluated under policy ──> Contribution Authority
        │                                          │
        │                                          ├── scope: performance + review
        │                                          ├── lifecycle: issued/expired/used/revoked
        │                                          └── provenance: evidence + issuer + policy
        │
        └──────────────────────────────────────────> Verified Review Invitation
                                                     │
                                                     ▼
                                                   Review
```

An audience account may link to the contributor identity, but it must not sit on the path that creates authority.

## Recommended Decision Order

1. Approve the product definition of verified attendance and evidence classes.
2. Decide whether `ReviewInvitation` remains the authority aggregate or becomes a delivery mechanism for a separate authority concept.
3. Define authority lifecycle, correction, revocation, and contribution treatment.
4. Define provider connection capability and scope for attendance evidence and authority issuance.
5. Define direct review-to-authority provenance and database invariants.
6. Define the audience identity/account linking model.
7. Extract authority enforcement into a focused application service.
8. Add migrations and tests only after the above decisions receive Engineering Review and Founder Approval.
9. Define analytical and AI provenance before derived intelligence launches.

## ADR Implications

- ADR-009 remains valid and should not be duplicated.
- ADR-015 supplies the governing identity/authority principle.
- A further ADR may be required if Encore introduces a first-class `AttendanceEvidence` or `ContributionAuthority` aggregate.
- The provider connection and capability model should extend or supersede relevant parts of ADR-006, ADR-010, ADR-011, and ADR-014 deliberately.
- Audience account architecture will require its own ADR because identity linking, consent, erasure, and provenance have durable consequences.

## Engineering Recommendation

Do not change application code as part of this governance work.

The first implementation proposal should be a narrow authority-integrity increment: define acceptable attendance evidence, preserve a direct authority-to-review relationship, and enforce the invariant that no review can be marked verified without explicit provenance. Audience account work and multi-provider generalization should follow, not precede, that integrity baseline.
