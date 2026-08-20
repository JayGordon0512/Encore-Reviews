# ADR-019: Explicit Review Verification, Moderation, and Publication

Encore exists to orchestrate the live entertainment ecosystem through trusted
experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-08-04
- Scope: Review provenance, revisions, moderation authority, public visibility,
  organisation permissions and takedown history
- Extends: ADR-009, ADR-012, ADR-013 and ADR-015
- Supersedes: Combined review status/publication behavior and organisation-owned
  public moderation decisions
- Depends on: ADR-017 and ADR-018

## Context

The current `reviews` table combines a `verified` boolean,
`verification_source`, `moderation_status` and moderation reason. Public pages
and scores treat `moderation_status=approved` as publication. Organisation
administrators for the reviewed show can approve or reject reviews; Encore
super administrators are deliberately read-only.

This conflates four different questions:

1. What evidence authorises the reviewer to contribute?
2. Does the content comply with Encore policy?
3. Is the review currently public?
4. Does it qualify under a reputation policy?

It also lets an organisation decide whether its own audience reviews are
public, conflicting with Encore's independent moderation and reputation
responsibility.

## Decision

### Separate records and state

Represent separately:

- `Review` — reviewer-authored rating/content and submission lifecycle;
- `ReviewRevision` — immutable snapshots of authored changes;
- `VerificationRecord` — provider/evidence class and policy establishing
  authority;
- `ModerationCase` — policy version, assessment, decision, reasons and appeal;
- `ReviewPublication` — public visibility, takedown/restoration and current
  moderation reference;
- reputation eligibility — evaluated by ADR-020's ScorePolicy.

A review can be verified but rejected by moderation. A moderation-approved
review can remain unpublished/taken down. A published review can be excluded by
a future score policy without rewriting verification or moderation history.

### Moderation authority

Encore owns content-policy decisions and public publication authority.

Organisation users may:

- view reviews for their scoped shows under approved privacy policy;
- submit an organisation response if/when that capability is approved;
- flag a review with an auditable reason;
- view status and permitted moderation outcome information.

Organisation ownership alone does not permit approving, rejecting, editing or
deleting reviewer content or changing verification/reputation state.

Encore moderation uses a named platform moderator authority distinct from
super-administrator customer-support access. Support and moderation permissions
are separately least-privileged and audited.

### Submission and revision

ADR-017 invitation redemption atomically creates:

- review;
- initial revision;
- verification record;
- pending moderation case;
- unpublished publication record;
- audit/outbox events.

Release 1 rating is an integer 1–5 under ScorePolicy v1. Reviewer edits append a
revision. A material edit returns the review to moderation and does not silently
replace already-published history.

Organisation users cannot rewrite rating/content. Administrative correction of
system metadata is a separate audited command.

### Publication and takedown

Publication requires:

- an active submitted review;
- a current approved Encore moderation decision;
- policy eligibility for public display;
- an idempotent publication command.

Takedown removes public visibility without deleting review, revisions,
verification or moderation history. It records actor, reason, source and time.
Restoration repeats the required checks.

Publication/takedown emits outbox events for reputation and search after commit.

### Verification changes and withdrawal

Consent withdrawal does not assert that historical eligibility evidence was
false. If evidence is later invalidated, append/transition verification evidence
under policy; do not overwrite moderation/publication history. Public label and
score effects follow explicit versioned policy.

Reviewer withdrawal/privacy handling changes public/submission state through a
controlled workflow and preserves only evidence required by approved policy.

## Consequences

- Existing customer moderation UI must be removed, disabled or changed to a
  flag/report workflow before Release 1 publication authority is enabled.
- A new platform moderator role/workflow and operating owner are required.
- Review reads join explicit current publication/moderation/verification state
  or use safe projections.
- More records and transitions increase implementation/testing effort but make
  decisions explainable and reversible.
- Existing combined fields remain during backfill/dual-read comparison and are
  retired only after reconciliation.
- Audit allowlists must minimise reviewer content/PII while recording policy,
  actor, reason and state change.
- Public pages no longer infer publication solely from organisation-controlled
  `moderation_status`.

## Alternatives considered

### Let each organisation moderate its own reviews

Rejected because commercial interest can influence visibility and reputation,
weakening Encore's independent trust proposition.

### Verified means automatically published

Rejected because attendance authority does not prove content-policy compliance.

### Keep one generic review status

Rejected because verification, moderation, publication, withdrawal and score
eligibility have independent actors, reasons and transitions.

### Delete rejected or taken-down reviews

Rejected because it destroys evidence, prevents appeals/reconciliation and can
produce unexplained score/history changes.

### Put all rules in Eloquent observers

Rejected because actor, policy, transaction and event intent would become
implicit and hard to test.

## Security and privacy implications

- Moderator access is platform-scoped, least-privileged, MFA-ready and audited.
- Organisation queries remain tenant-scoped and exclude unrestricted contact
  evidence.
- Public projections expose only approved reviewer display and content.
- Moderation rationale/risk flags are restricted operational data.
- Retention, reviewer rights, appeals and data-subject handling require approved
  policies before G5.

## Migration implications

1. Profile current verification/moderation combinations and organisation-made
   decisions.
2. Add revision, verification, moderation and publication tables additively.
3. Backfill explicit states using deterministic rules and an exception report.
4. Establish Encore moderator authority before switching decisions.
5. Dual-read/compare public and admin views.
6. Switch publication/reputation to explicit records behind flags.
7. Retire organisation approval controls and legacy combined fields only after
   stability evidence.

No past organisation approval is reinterpreted as an independent Encore policy
decision without an approved backfill rule.

## Acceptance conditions

- Verified/rejected review remains unpublished and excluded from reputation.
- Pending/unapproved review cannot publish.
- Organisation users cannot approve, reject, edit/delete reviewer content or
  alter verification/reputation state.
- Encore moderator decisions include actor, policy version, reason and audit.
- Edits append revisions and re-enter moderation under approved policy.
- Takedown/restoration preserves history and updates downstream projections once.
- Cross-tenant and platform-role negative tests pass.
- Backfill exception/reconciliation report has no unresolved critical finding.
