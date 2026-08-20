# ADR-020: Encore-Owned Versioned Reputation

Encore exists to orchestrate the live entertainment ecosystem through trusted
experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-08-04
- Scope: Rating policy, qualifying reviews, aggregate calculation, snapshots,
  public display and provider output
- Extends: ADR-000, ADR-003, ADR-004 and ADR-015
- Depends on: ADR-018 and ADR-019

## Context

Current public and organisation pages load reviews whose
`moderation_status=approved` and calculate the average/count in application
memory. There is no versioned score policy, explicit publication state,
authoritative reputation profile, distribution, snapshot history or provider
output authority.

This makes score meaning implicit in query code. It cannot explain how a score
was calculated at a point in time, rebuild reliably after policy/change events,
or distinguish Encore's reputation authority from a provider display.

## Decision

### Ownership

Encore exclusively owns authoritative review-derived reputation. TicketPal and
other approved consumers may receive/display permitted outputs but cannot write
reviews, verification, moderation, ScorePolicy, reputation profiles or
snapshots.

Commercial/provider relationships do not change moderation or score rules.

### ScorePolicy

Use immutable, versioned `ScorePolicy` records defining:

- valid rating scale;
- qualifying verification/evidence classes;
- required publication/review state;
- treatment of withdrawn/invalidated evidence;
- rounding/display rules;
- minimum public review count;
- effective dates and approval provenance.

Release 1 uses one integer overall rating from 1 to 5. The product owner must
approve the minimum-count/display value before G3.

### Authoritative aggregate

Maintain one current `ReputationProfile` per show and effective score policy,
containing:

- exact/internal average and approved display value;
- eligible review count;
- 1–5 rating distribution;
- calculated-through time and source/cause event;
- current/stale/recalculating/failed state.

Append a `ReputationSnapshot` when the public aggregate changes, recording
policy version, score/count/distribution, calculation time, cause event and
correlation ID.

PostgreSQL review/publication/evidence records remain the source of truth. The
profile and all search/cache projections are rebuildable.

### Calculation and events

ADR-018 handles `ReviewPublished`, `ReviewTakenDown` and relevant policy/evidence
events idempotently. Recalculation:

1. resolves the effective ScorePolicy;
2. selects only current qualifying published reviews;
3. locks the show/policy profile;
4. calculates average, count and distribution;
5. updates current profile;
6. appends a snapshot only when the public result changed;
7. emits `ReputationUpdated` after commit.

Duplicate event processing changes the result at most once. A scheduled/full
rebuild must equal the incremental profile.

### Public and provider output

Public pages consume a safe reputation read model, not ad hoc averages from
loaded Eloquent collections.

Approved provider output may contain:

- Encore show/public review-page reference;
- displayed score where policy permits;
- eligible review count;
- score-policy/output schema version;
- updated time.

Provider delivery is separately authenticated, idempotent, retryable and
audited. Provider acknowledgement does not mutate the source aggregate.

## Consequences

- Current live averages remain compatibility displays until backfill and
  reconciliation succeed.
- Publishing/taking down reviews becomes eventually consistent with the public
  profile by a bounded observable worker delay.
- Policy changes can be explained and rebuilt without rewriting reviews.
- Snapshot storage grows and needs retention/archival policy.
- Search and TicketPal displays can converge from versioned Encore output.
- Organisation users cannot override scores, counts or rating distribution.
- UI must distinguish insufficient-count behaviour from a zero/no-rating score.

## Alternatives considered

### Calculate averages ad hoc in every controller/view

Rejected because policy is duplicated, historical meaning is lost and provider/
search displays can diverge.

### Let TicketPal calculate its own Encore score

Rejected because multiple implementations would weaken consistency and Encore's
commercial/reputational independence.

### Store only the average

Rejected because count/distribution are required for explanation,
reconciliation and display policy.

### Recalculate synchronously during publication

Rejected as the general path because aggregate/search/provider failure would
couple publication to secondary work. Publication records committed intent;
ADR-018 processes it reliably.

### Make snapshots the source of truth

Rejected. Reviews and explicit workflow state remain authoritative; snapshots
are explainable materialisations.

## Security and integrity implications

- Only authorised system/policy workflows write profiles/snapshots.
- Provider and organisation credentials receive read/delivery scope only.
- Database constraints enforce non-negative counts, rating ranges and one
  current show/policy profile.
- Distribution totals are validated against eligible count.
- Calculation and output logs contain no review content/contact PII.

## Migration implications

1. Approve and seed ScorePolicy v1.
2. Add profile/snapshot tables and constraints.
3. Compute current qualifying source set under ADR-019 states.
4. Compare new count/average to every existing public/admin display.
5. Resolve exceptions; switch reads behind a feature flag.
6. Add full rebuild and provider-output reconciliation.
7. Retire ad hoc score calculations after zero-use evidence.

## Acceptance conditions

- Rating checks enforce 1–5.
- Only ScorePolicy-qualifying published reviews affect reputation.
- Average, count and distribution reconcile with source rows.
- Full rebuild equals incremental calculation.
- Duplicate publication/takedown events change the profile once.
- Takedown/restoration and evidence-policy cases converge correctly.
- Public minimum-count behaviour follows approved ScorePolicy v1.
- TicketPal/provider output cannot mutate Encore source records.
- Search/public/provider projections rebuild and reconcile from PostgreSQL.
