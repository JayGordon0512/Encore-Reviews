# ADR-017: Consented Eligibility and Encore-Owned Invitations

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Proposed
- Date: 2026-08-04
- Scope: Review eligibility, consent evidence, reviewer contact, invitation
  scheduling, tokens, delivery, withdrawal and submission authority
- Extends: ADR-009 and ADR-015
- Supersedes: Current provider-owned direct invitation creation and token return
- Depends on: ADR-016

Implementation note (27 August 2026): the invitation issuer, keyed token
digests, token-free delivery evidence, replacement-on-failure behavior and
consent-withdrawal revocation are implemented behind the disabled issuing flag.
This evidence does not change the Proposed status or authorize activation.

Implementation note (30 August 2026): new invitation emails place the bearer
capability in the URL fragment. Browser code removes the fragment immediately,
posts the token to a rate-limited exchange endpoint and binds the invitation to
a rotated server session. The review form and its submission request contain no
plaintext token. The legacy token-body API remains for bounded compatibility;
new email journeys do not use it.

## Context

The current TicketPal endpoint creates an Encore invitation immediately for a
provided performance and email address, marks it sent, returns the raw token to
TicketPal and stores the encrypted response for replay. It has no distinct
eligibility or consent-evidence record, no consent-withdrawal operation and no
Encore-owned post-performance scheduler.

This conflicts with the agreed ownership boundary. TicketPal owns bookings,
tickets, payments, fulfilment and attendance evidence. Encore owns reviewer
identity, consented review processing, invitations, moderation, reviews and
reputation. TicketPal may prove eligibility but must not own or send Encore's
review capability token.

## Decision

### Eligibility hand-off

TicketPal sends a signed Provider API v2 eligibility event only for a paid
booking with current explicit `encore_review` consent. The minimised evidence
contains:

- provider event and booking identifiers;
- mapped show and performance identifiers/context;
- reviewer name/email needed for the approved purpose;
- admission quantity as evidence;
- consent purpose, policy version and capture time.

It excludes payment details, marketing consent, organiser data and ticket/
payment records.

Encore authenticates the provider, resolves mappings and records:

- one provider event/idempotency outcome;
- one reviewer/contact resolution;
- one immutable consent-evidence record;
- one `ReviewEligibility`;
- at most one scheduled `ReviewInvitation`.

Admission quantity does not create multiple invitations. Release 1 permits one
review per eligibility.

### Ownership and scheduling

Encore owns whether and when an invitation is issued. Release 1 schedules the
invitation after the recorded performance end under a versioned product policy.
Exact delay, expiry and reminder cadence require approved values.

An inactive organisation, cancelled/rescheduled performance, withdrawn/
invalidated eligibility or suppressed contact prevents issue or reminder under
its explicit policy.

The current provider-created invitation endpoint is not the Release 1 hand-off.
It must be disabled for new v2 eligibility processing before TicketPal delivery
is enabled.

### Reviewer contact

Reviewer identity is Encore-owned and platform-wide. Store contact values in
approved protected/encrypted form and use a versioned keyed fingerprint for
lookup and duplicate control. Unkeyed email hashes are legacy compatibility
data and are migrated through a collision report.

Organisations and providers do not receive unrestricted reviewer identity.

### Invitation tokens

Invitation tokens are cryptographically random bearer capabilities with at
least 128 bits of entropy. They are:

- purpose-bound;
- versioned;
- stored only as keyed digests;
- expiring;
- revocable;
- atomic and single-use.

Plaintext tokens never enter database records, provider responses, generic
queue payloads, failed-job storage, ordinary logs or audit metadata.

Email links must not place the capability in a query string or path. The token
travels in a URL fragment, which browsers do not include in HTTP request
targets, and is exchanged in a POST body for a rotated, server-side session.
Request-body logging is prohibited on the exchange endpoint.

The issuing worker creates the token/digest, commits issued state and a
token-free notification delivery record, then uses the plaintext token only in
memory for immediate post-commit dispatch. If dispatch fails after commit, a
retry revokes the undelivered token and generates a replacement.

### Withdrawal

TicketPal sends a signed v2 withdrawal event for the provider booking and
purpose. Encore:

- records the event idempotently;
- marks matching eligibility withdrawn;
- revokes a scheduled/issued unredeemed invitation;
- suppresses pending reminders;
- retains consent evidence explaining why processing began;
- returns the same HTTP shape/status whether or not a target existed.

Original eligibility replay cannot reverse withdrawal.

Withdrawal after a review is submitted stops invitation communication but does
not silently delete or rewrite the review. Reviewer privacy/deletion requests
use a separate controlled workflow.

### Review submission authority

A valid issued invitation authorises one review for its performance/eligibility.
Redemption locks the invitation and atomically creates the review, initial
revision, verification record, pending moderation case, audit and outbox intent.
Exactly one concurrent redemption succeeds.

Expired, revoked, used and unknown tokens return a safe indistinguishable public
error category where practical.

## Consequences

- TicketPal no longer receives or delivers Encore invitation tokens for the v2
  flow.
- Encore needs a protected contact store, scheduler, mail dispatcher and
  delivery operations.
- Consent/eligibility and invitation state are separate, so historical evidence
  does not imply current permission to communicate.
- Invitation delivery can be retried without repeating TicketPal booking work.
- Existing invitations require additive mapping/backfill or explicit legacy
  treatment; provenance is not invented.
- The fragment/POST/session exchange requires JavaScript for emailed review
  links and a functioning session store.
- One review per eligibility is database-enforced; future attendee-level
  invitations require a separate decision.

## Alternatives considered

### TicketPal creates and sends the invitation

Rejected because it transfers Encore token lifecycle, suppression, reviewer
identity and audit responsibility to the ticketing provider.

### Send email during the provider request

Rejected because email availability would affect hand-off latency/reliability
and could create ambiguous retries.

### Store encrypted plaintext tokens for retry

Rejected as the target because any decryptable token store expands breach and
key-rotation impact. Delivery failure regenerates a token instead.

### Stateless signed tokens only

Rejected because withdrawal, single-use, revocation and concurrent redemption
need authoritative server-side state.

### Treat consent evidence as permanently active consent

Rejected because evidence of capture and current permission/suppression are
different concerns.

## Security and privacy implications

- Contact encryption/fingerprinting, key rotation and collision handling require
  privacy/security approval.
- Invitation links must avoid analytics/referrer leakage and be excluded from
  logs/search indexes.
- Consent, contact, token digest and delivery retention require approved
  schedules.
- Pre-send state is rechecked to close withdrawal/suppression races.
- Rate limits apply to public token lookup/submission and regeneration.

## Migration implications

1. Profile current reviewers/invitations without outputting contact/token values.
2. Add contact points, eligibility, consent and expanded invitation state
   additively.
3. Backfill only demonstrable provider/consent provenance.
4. Migrate active token digests/version and regenerate uncertain tokens.
5. Dual-read during a bounded compatibility window; new writes use v2 state.
6. Disable current provider direct-invitation use before v2 enablement.
7. Retire legacy email/token/provider columns only after zero-use and recovery
   evidence.

## Acceptance conditions

- Paid/consented v2 fixture creates one eligibility and scheduled invitation.
- Unpaid, declined or consent-absent TicketPal flows emit no eligibility.
- Duplicate/concurrent delivery creates one eligibility/invitation.
- Withdrawal revokes/suppresses unredeemed activity without target disclosure.
- Token expiry, replay, regeneration and concurrent redemption tests pass on
  PostgreSQL.
- Runtime scans find no plaintext invitation token in database, queues, failed
  jobs, logs or audit.
- TicketPal outage does not prevent Encore processing of accepted work, and
  Encore outage does not fail TicketPal sales/fulfilment.
