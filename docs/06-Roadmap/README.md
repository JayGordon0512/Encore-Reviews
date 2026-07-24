# Capability Roadmap

- Status: Authoritative planning reference
- Owner: Encore Reviews product and engineering
- Last updated: 15 July 2026

## Purpose

This roadmap plans Encore Reviews as a set of durable business and platform capabilities. It does not plan screens or isolated features. A user interface, API endpoint, background job, report, or provider adapter is a delivery mechanism within a capability, not a roadmap unit by itself.

Roadmap decisions are governed by the [Operating Principles](../00-Vision/Operating-Principles.md). Every significant roadmap initiative must complete Strategic Review, Engineering Review, and Founder Approval before implementation proceeds, and must answer the Product Guardian questions before it is promoted into delivery.

The proposed TicketPal transition to Provider API v2 is governed by the [TicketPal Provider v2 Migration Programme](TicketPal-Provider-v2-Migration.md). It does not add Performance Completed or Ticket Scanned to the implemented capability baseline.

[Sprint 1 Enterprise Integration](Sprint-1-Enterprise-Integration.md) is a planning-only set of capability placeholders. It does not authorize implementation or supersede the capability portfolio below.

[Engineering Governance Closure](Engineering-Governance-Closure.md) is **Deferred**. Current priority has shifted to **Encore Product Design** and the **Showcase Release**; no implementation or GitHub issue is authorized by that backlog record.

The roadmap distinguishes implemented behavior from proposed work. A proposed API, event, queue, entity, or database change is not part of the current platform contract until it has been designed, implemented, tested, documented, and released.

## Planning principles

1. Protect trust, tenant isolation, and recoverability before increasing product breadth.
2. Extend Encore's provider-neutral domain rather than introducing provider terminology into core ownership.
3. Preserve the distinction between identity/access and verified contribution authority under ADR-015.
4. Deliver end-to-end capability increments, including security, operations, tests, and documentation.
5. Use synchronous processing for request-critical state changes and queues for independently retryable work after ADR-007 and ADR-008 are accepted.
6. Treat API and event payloads as governed contracts with explicit compatibility rules.
7. Validate scalability through measured demand; do not introduce distributed infrastructure without an operational requirement.

## Portfolio priority

| Priority | Capability | Current state | Intended outcome |
| --- | --- | --- | --- |
| P0 | C01 — Platform assurance and operability | PostgreSQL CI and dependency gates implemented | A secure, observable, recoverable production foundation |
| P0 | C02 — Organisation identity and tenant governance | Policy authorization and admin audit implemented | Consistent lifecycle, authorization, and accountability for every tenant |
| P0 | C03 — Verified review integrity | Core workflow implemented | Durable proof, moderation, and publication integrity |
| P0 | C04 — Provider ingestion and reconciliation | Signed, replay-safe TicketPal ingestion implemented | Reliable synchronisation and reconciliation at enterprise volume |
| P1 | C05 — Invitation orchestration and delivery | Invitation creation implemented | Idempotent delivery and lifecycle management |
| P1 | C06 — Moderation governance | Policy-led, audited basic moderation implemented | Governed review operations and decision history |
| P1 | C07 — Public review intelligence | Basic discovery and scores implemented | Scalable, explainable audience insight |
| P2 | C08 — Organisation analytics and data access | Not implemented | Governed operational insight and exports |
| P2 | C09 — Review distribution and widgets | Not implemented | Secure distribution of approved review evidence |
| P2 | C10 — Multi-provider integration ecosystem | TicketPal only | Repeatable onboarding and operation of additional providers |

P0 capabilities are prerequisites for production confidence. P1 capabilities expand trusted platform operation. P2 capabilities broaden the value and reach of established platform data. Priority does not imply a delivery date.

## Capability C01 — Platform assurance and operability

**Business Goal**

Operate Encore securely and predictably, with recoverable data, controlled releases, measurable service health, and evidence that the production architecture behaves as designed.

**Domain Entities**

No new business aggregate is required initially. Implemented operational records include administrative audit logs and provider integration events. Future records may include deployment metadata, job failures, and integration health observations. Each retained operational record requires an explicit retention policy.

**API Contracts**

Current business APIs remain unchanged. Planned operational contracts include authenticated health/readiness checks and internal diagnostic correlation identifiers. Operational endpoints must not disclose secrets, customer data, stack traces, or infrastructure topology.

**Database Impact**

- Validate all migrations against PostgreSQL in continuous integration.
- Enforce missing uniqueness and lookup indexes identified by production query patterns.
- Define backup, restore, retention, migration rollback, and disaster-recovery procedures.
- Define cleanup and retention enforcement for audit and provider-event records.

**Events**

No domain events are required to establish the baseline. Deployment and service telemetry are operational signals, not domain events. If durable domain events are introduced, ADR-007 must first be accepted with transaction and delivery guarantees.

**Queues**

No business queue is currently implemented. Before queue-backed capabilities launch, establish worker supervision, retry policies, failure retention, backlog monitoring, and recovery procedures as required by proposed ADR-008.

**Security**

- Remove critical and high dependency vulnerabilities before release.
- Separate development and production configuration.
- Manage secrets outside source control and support rotation.
- Apply least-privilege database and infrastructure access.
- Define rate limits, security headers, log redaction, incident response, and vulnerability-management procedures.

**Testing**

- Run unit and feature tests, Pint, static analysis, dependency audits, asset builds, and PostgreSQL integration tests in CI.
- Test fresh migration, upgrade, rollback where supported, backup restoration, and deployment health checks.
- Establish measurable coverage for security-critical and transactional workflows.

**Documentation**

Maintain production deployment, monitoring, alerting, backup, restore, incident, secret-rotation, release, and rollback runbooks in `docs/05-Operations`.

**Dependencies**

Hosting and service-level objectives; production database selection and access model; secrets manager; monitoring and alerting platform; accepted release policy.

**Acceptance Criteria**

- Every protected branch passes automated quality and PostgreSQL gates.
- No unresolved critical dependency advisory reaches production.
- A fresh environment can be deployed from documented instructions.
- Backup restoration and rollback procedures are exercised successfully.
- Health, error rate, latency, database, and worker signals have owners and alert thresholds.
- Production secrets and services are not exposed through development defaults.

## Capability C02 — Organisation identity and tenant governance

**Business Goal**

Allow Encore to govern organisations, administrators, support access, and organisation-owned data without cross-tenant exposure.

**Domain Entities**

Implemented: `Organisation`, `User`, `Show`, and `Venue` ownership, policy-led administrative authorization, and administrative audit records. Planned extensions may include role/permission definitions and provider credentials. `Organisation` remains the root ownership entity.

**API Contracts**

Current administration uses session-authenticated web contracts. Any future organisation-management API must be versioned, authenticate an Encore administrator, enforce policy authorization, and never infer tenancy from caller-supplied identifiers alone.

**Database Impact**

- Constrain user roles and ownership invariants.
- Preserve organisation foreign keys on every organisation-owned entity.
- Strengthen immutable audit records with retention, database-role protection, and external tamper evidence.
- Define deactivation, retention, export, and deletion semantics before self-service lifecycle work.

**Events**

Proposed facts include `OrganisationCreated`, `OrganisationActivated`, `OrganisationDeactivated`, `OrganisationUserInvited`, and `OrganisationUserAccessChanged`. These are future internal contracts subject to ADR-007.

**Queues**

Potential queued reactions include account invitations, security notifications, lifecycle exports, and deletion/retention workflows. Authoritative access changes remain synchronous.

**Security**

- Centralize authorization in policies or an equivalently explicit tenant boundary.
- Require MFA for Encore super administrators and define customer-admin MFA policy.
- Enforce account activation, password reset, verified identity, session invalidation, and support-access rules.
- Record the actor and target of every privileged mutation.

**Testing**

Cover every role/action combination, nested-resource tampering, inactive users and organisations, session lifecycle, privilege escalation, cross-organisation access, and read-only support inspection.

**Documentation**

Maintain the role matrix, tenant invariants, organisation lifecycle, support-access policy, audit requirements, and account-recovery procedures.

**Dependencies**

C01 platform assurance; agreed identity provider strategy; legal retention/deletion requirements; product decisions for self-service onboarding.

**Acceptance Criteria**

- Every organisation-owned query and mutation has an explicit, tested authorization rule.
- Customer administrators cannot observe or mutate another organisation's data.
- Support access is attributable and read-only unless a separately authorized action exists.
- Role and organisation invariants are enforced by application and database controls.
- Privileged lifecycle changes produce immutable audit evidence.

## Capability C03 — Verified review integrity

**Business Goal**

Ensure every verified audience review is traceable to valid evidence for a specific performance, is submitted at most once per invitation, and is excluded from public output until approved.

Identity alone does not grant review authority. A valid performance-level invitation supplies the bounded contribution authority under [ADR-015](../02-ADR/ADR-015-authority-through-verification.md).

**Domain Entities**

Implemented: `Performance`, `ReviewInvitation`, `Reviewer`, and `Review`. Planned integrity improvements include an explicit review-to-invitation relationship and versioned pseudonymous identity derivation.

**API Contracts**

Current contract: `POST /api/reviews`. The contract must retain bounded validation, atomic invitation consumption, stable validation errors, and no exposure of email or token hashes. A future version must define compatibility before changing response or error semantics.

**Database Impact**

- Uniquely index invitation token hashes and reviewer identity hashes.
- Consider linking each review directly to the invitation used to authorize it.
- Enforce rating, moderation, verification, and lifecycle invariants with appropriate constraints.
- Define retention and erasure behavior for pseudonymized reviewer data.

**Events**

Proposed facts include `ReviewSubmitted`, `ReviewInvitationConsumed`, and `ReviewVerificationFailed`. Security failures may be telemetry rather than durable domain events. Publication events belong to C06.

**Queues**

Review creation and invitation consumption remain synchronous and transactional. Independent fraud signals, notifications, analytics, and retention processing may run after commit.

**Security**

- Rate-limit public submissions.
- Use keyed, versioned pseudonymous identifiers where privacy requirements demand resistance to dictionary attacks.
- Bound all text, tags, and metadata.
- Avoid logging raw invitation tokens, email addresses, or derived identifiers.

**Testing**

Cover valid submission, token reuse, expiry boundaries, email mismatch, concurrent submission, malformed and oversized input, transaction rollback, reviewer identity races, and public exclusion of pending/rejected reviews.

**Documentation**

Document verification evidence, reviewer pseudonymization, token lifecycle, data retention, error contracts, moderation dependency, and privacy limitations.

**Dependencies**

C01 platform assurance; C02 tenant governance; performance data from C04; privacy and retention decisions; C06 moderation governance for publication.

**Acceptance Criteria**

- A valid invitation can authorize exactly one committed review.
- Concurrent attempts cannot create duplicate reviewers or reviews from the same evidence.
- The review retains sufficient provenance for support and audit investigation.
- Sensitive identity material is bounded, protected, and excluded from responses and logs.
- Only approved reviews can reach public aggregates or distribution channels.

## Capability C04 — Provider ingestion and reconciliation

**Business Goal**

Maintain accurate Encore shows, performances, venues, and ownership mappings despite repeated, delayed, duplicated, or out-of-order provider deliveries.

**Domain Entities**

Implemented: provider identity fields on `Show` and `Performance`, plus organisation-owned `Venue`. Planned additions may include provider integration, credential, delivery, checkpoint, and reconciliation records without changing the core domain language.

**API Contracts**

Current TicketPal contracts are `POST /api/ticketpal/shows/upsert`, `POST /api/ticketpal/performances/upsert`, and `POST /api/ticketpal/invitations`. They require signed event identity and provide synchronous duplicate-response replay. Planned evolution must provide versioning, provider-scoped credentials, consistent error envelopes, bounded batch semantics where required, and reconciliation behavior.

**Database Impact**

- Preserve unique provider identity keys for shows and performances.
- Make concurrent upserts deterministic through database-authoritative conflict handling.
- Enforce cleanup and long-term retention policy for delivery/idempotency state.
- Retain reconciliation checkpoints and failures according to an operational retention policy.

**Events**

Proposed facts include `ShowSynchronized`, `PerformanceSynchronized`, `VenueResolved`, `ProviderDeliveryRejected`, and `ProviderReconciliationCompleted`. Payloads must use stable Encore identifiers and versioned provider metadata.

**Queues**

Request-critical validation may remain synchronous. Bulk ingestion, scheduled reconciliation, retries, enrichment, and drift detection should be queue-backed after C01 establishes worker operation and ADR-008 is accepted.

**Security**

- Replace the global secret with scoped, revocable, rotatable provider credentials.
- Replace the application-wide secret with provider-scoped, rotatable credentials while retaining signed timestamps and replay protection.
- Apply provider-specific rate and payload limits.
- Prevent a provider credential from assigning or mutating data outside its authorized organisation/integration scope.

**Testing**

Cover repeat and concurrent delivery, reordered updates, provider-key collisions, ownership conflicts, timestamp normalization, invalid credentials, replay windows, partial failures, reconciliation repair, and PostgreSQL constraint behavior.

**Documentation**

Publish versioned provider contracts, authentication and rotation procedures, idempotency rules, timestamp semantics, reconciliation runbooks, failure codes, and integration support responsibilities.

**Dependencies**

C01 platform assurance; C02 organisation governance; accepted event/queue decisions for asynchronous orchestration; provider agreements for identifiers and delivery semantics.

**Acceptance Criteria**

- Repeated or concurrent delivery converges on one correct Encore record.
- A provider identity cannot silently move between Encore owners or parent entities.
- Every accepted or rejected delivery has a correlation identifier and observable outcome.
- Reconciliation identifies and safely repairs missing or divergent records.
- Provider credential compromise has a bounded, revocable scope.

## Capability C05 — Invitation orchestration and delivery

**Business Goal**

Issue the right review invitation once, deliver it through approved channels, and provide an observable lifecycle from eligibility to use or expiry.

The invitation represents contribution authority, not account access. Future audience membership must not replace or broaden that authority implicitly.

**Domain Entities**

Implemented: `ReviewInvitation`. Planned concepts may include an invitation idempotency identity, delivery attempt, delivery channel, suppression, and delivery status. These should not be added until channel and retention requirements are agreed.

**API Contracts**

Current TicketPal contract: `POST /api/ticketpal/invitations`, which creates one invitation per unique signed provider event and safely replays the encrypted original response. Planned evolution must define eligibility evidence, channel-independent issuance, token-return policy, and long-term recovery after replay retention expires.

**Database Impact**

- Add a stable uniqueness rule for provider-driven invitation issuance.
- Uniquely index token hashes.
- Store delivery attempts separately from authoritative invitation eligibility.
- Define suppression, expiry, cancellation, resend, retention, and erasure semantics.

**Events**

Proposed facts include `ReviewInvitationIssued`, `ReviewInvitationDeliveryRequested`, `ReviewInvitationDelivered`, `ReviewInvitationDeliveryFailed`, `ReviewInvitationExpired`, and `ReviewInvitationConsumed`.

**Queues**

Issuance may remain synchronous when a provider needs an authoritative response. Email or messaging delivery, retries, expiry processing, and delivery-status polling should be queued and idempotent.

**Security**

- Never persist or log recoverable raw tokens.
- Limit token exposure to the minimum required delivery boundary.
- Authenticate and scope provider issuance.
- Apply anti-enumeration responses, delivery-rate limits, suppression controls, and redaction of recipient data.

**Testing**

Cover provider retries, duplicate tickets/bookings, delivery retries, expired and cancelled invitations, token uniqueness, redaction, queue failure/recovery, suppression, and exactly-once effective issuance under concurrent requests.

**Documentation**

Document eligibility, idempotency, token handling, channel ownership, delivery state, retry policy, suppression, expiry, privacy, and operational recovery.

**Dependencies**

C01 platform assurance and queue operation; C03 review integrity; C04 provider identity and reconciliation; selected delivery provider; privacy and communication-consent requirements.

**Acceptance Criteria**

- The same provider eligibility evidence cannot create unintended duplicate invitations.
- Delivery failures are retryable and visible without recreating authoritative eligibility.
- Raw tokens and recipient data are protected through storage, logging, and support workflows.
- Operators can determine whether an invitation was issued, delivered, failed, expired, or consumed.

## Capability C06 — Moderation governance

**Business Goal**

Enable organisations to make accountable publication decisions while preserving Encore's trust rules, support boundaries, and a complete history of moderation actions.

**Domain Entities**

Implemented: moderation state and reason on `Review`. Planned: append-only moderation decision/history records and, if product policy requires them, moderation policy or escalation records.

**API Contracts**

Current moderation uses session-authenticated organisation administration routes. Future contracts must define allowed transitions, conflict handling, actor attribution, reason requirements, bulk limits, and read-only support behavior.

**Database Impact**

- Add immutable moderation history instead of relying only on the latest state.
- Constrain moderation states and transitions at the appropriate service/database boundaries.
- Index organisation, status, and submission-time access paths.

**Events**

Proposed facts include `ReviewApproved`, `ReviewRejected`, `ReviewModerationReopened`, and `ReviewPublicationChanged`. Publication reactions must occur after the moderation transaction commits.

**Queues**

The decision remains synchronous. Notifications, search updates, public aggregate refresh, analytics, and widget invalidation may run asynchronously and must tolerate duplicate events.

**Security**

- Enforce organisation ownership through policies.
- Attribute every action to an authenticated active user.
- Keep Encore support inspection read-only unless a separately governed intervention capability is approved.
- Sanitize reasons and prevent sensitive internal notes from entering public output.

**Testing**

Cover the transition matrix, repeat decisions, cross-tenant attempts, inactive actors, support-user restrictions, audit immutability, concurrent decisions, and publication/cache consistency.

**Documentation**

Maintain moderation policy, transition rules, reason handling, escalation, support authority, audit retention, and publication side effects.

**Dependencies**

C01 observability; C02 authorization and audit; C03 review integrity; C07 public aggregation; legal/product moderation policy.

**Acceptance Criteria**

- Only authorized organisation actors can change a review's publication state.
- Every decision records actor, time, prior state, resulting state, and reason where required.
- Public outputs consistently exclude non-approved reviews.
- Concurrent or repeated decisions resolve deterministically and remain auditable.

## Capability C07 — Public review intelligence

**Business Goal**

Help audiences discover relevant live performances and understand trustworthy, explainable review evidence at predictable response times.

**Domain Entities**

Implemented entities remain `Show`, `Performance`, `Venue`, and approved `Review`. Planned read models or search documents are projections, not new ownership roots or sources of truth.

**API Contracts**

Current delivery is server-rendered public pages. A future public read API must expose only approved data, use pagination, define stable aggregate semantics, support cache validation, and be versioned before external consumption.

**Database Impact**

- Add indexes for approved review aggregation and ordered public discovery.
- Introduce search documents or aggregate projections only when measured query demand justifies them.
- Keep projections rebuildable from authoritative domain data.

**Events**

Proposed projection triggers include `ShowSynchronized`, `PerformanceSynchronized`, and `ReviewPublicationChanged`. Event-driven projections depend on accepted delivery guarantees.

**Queues**

Search indexing, aggregate projection, cache invalidation, and projection rebuilds may be queued. Public reads must remain available during worker interruption, with documented freshness expectations.

**Security**

- Expose only approved reviews and explicitly public show/venue fields.
- Rate-limit public APIs and protect search from abusive query cost.
- Prevent unpublished, tenant-internal, provider-secret, and reviewer-identity data from entering projections or caches.

**Testing**

Cover publication filtering, score and recommendation calculations, empty states, pagination, ordering, search relevance fixtures, cache invalidation, projection rebuilds, accessibility, and performance budgets.

**Documentation**

Document public field classification, aggregate formulas, freshness expectations, pagination, search semantics, accessibility standard, and cache/projection recovery.

**Dependencies**

C01 performance monitoring; C03 verified reviews; C04 accurate show/performance data; C06 publication governance; measured search and traffic requirements.

**Acceptance Criteria**

- Pending and rejected reviews never influence public content or aggregates.
- Scores and recommendation rates are reproducible from documented rules.
- Public collections are paginated and meet an agreed performance budget at target volume.
- Search or aggregate projections can be rebuilt without data loss.

## Capability C08 — Organisation analytics and data access

**Business Goal**

Give organisations trustworthy insight into audience response and operational review activity without exposing another organisation's data or overstating statistical meaning.

**Domain Entities**

Authoritative inputs are organisation-owned shows, performances, invitations, and reviews. Planned analytics datasets, snapshots, export requests, and saved report definitions are derived records rather than transactional sources of truth.

**API Contracts**

No analytics API exists. Planned contracts must define metric names, dimensions, time zones, filters, pagination, export limits, asynchronous status, retention, and schema/version compatibility.

**Database Impact**

- Begin with indexed aggregate queries.
- Add materialized views, snapshots, or a separate analytical store only after workload measurement.
- Persist large export requests and expiration metadata when asynchronous exports are introduced.

**Events**

Potential inputs include review submission/publication, invitation lifecycle, and performance synchronization facts. Metric ownership and late-event correction semantics must be defined before events feed analytics.

**Queues**

Large exports, projection refreshes, scheduled reports, and historical recalculation should be queued with cancellation, expiry, retries, and resource limits.

**Security**

- Scope every metric and export to the authorized organisation.
- Apply minimum cohort and suppression rules where re-identification is possible.
- Use signed, short-lived export access and record export activity.
- Prevent spreadsheet formula injection and unbounded export workloads.

**Testing**

Cover metric correctness, tenant isolation, time-zone boundaries, late data, publication changes, empty cohorts, export injection, large-volume behavior, and projection rebuild equivalence.

**Documentation**

Maintain a metric catalogue with formulas, source fields, inclusion rules, freshness, correction behavior, privacy controls, export schema, and known interpretation limits.

**Dependencies**

C01 operational capacity; C02 tenant governance; C03 integrity; C06 moderation history; C07 canonical aggregate definitions; product and privacy decisions for metrics.

**Acceptance Criteria**

- Every metric has one documented definition and an executable correctness test.
- Organisations can access only their own analytics and exports.
- Publication changes and late data produce defined, reconcilable results.
- Expensive reports cannot degrade transactional workloads beyond agreed limits.

## Capability C09 — Review distribution and widgets

**Business Goal**

Allow organisations and approved partners to distribute current Encore review evidence while preserving attribution, moderation state, integrity, and platform control.

**Domain Entities**

Planned concepts may include `Widget`, distribution credential, configuration, allowed origin, publication snapshot, and usage record. Widgets remain owned by an `Organisation` and consume approved review data.

**API Contracts**

No widget contract exists. A future contract must define embedding mode, versioning, approved fields, branding/attribution, origin policy, caching, revocation, accessibility, fallback behavior, and compatibility guarantees.

**Database Impact**

- Persist organisation-owned widget configuration and credential metadata.
- Index active/revoked distribution identities.
- Store usage analytics only under an explicit privacy and retention policy.

**Events**

Potential facts include `WidgetCreated`, `WidgetConfigurationChanged`, `WidgetRevoked`, and publication-change events used for invalidation. Event contracts remain proposed.

**Queues**

Cache warming, snapshot generation, invalidation, usage aggregation, and bulk refresh may be queued. Revocation must take effect synchronously or within a documented maximum interval.

**Security**

- Enforce organisation ownership and allowed origins.
- Use revocable public identifiers rather than administrative credentials.
- Protect endpoints against scraping abuse and configuration injection.
- Apply CSP-compatible delivery, safe rendering, data minimization, and short cache windows for revocation-sensitive state.

**Testing**

Cover tenant isolation, origin restrictions, revocation, moderation invalidation, escaping, CSP behavior, accessibility, responsive rendering, cache freshness, API compatibility, and abusive request patterns.

**Documentation**

Publish integration guidance, version policy, accessibility and branding requirements, origin setup, security model, data fields, caching behavior, revocation, and troubleshooting.

**Dependencies**

C01 production delivery and monitoring; C02 organisation authorization; C06 moderation; C07 stable public read contracts; legal/product decisions for attribution and branding.

**Acceptance Criteria**

- A widget exposes only approved, documented public data for its owning organisation.
- Configuration changes and revocation propagate within documented bounds.
- Embedded content is accessible, safely rendered, versioned, and operationally observable.
- Moderation changes cannot leave rejected content indefinitely distributed.

## Capability C10 — Multi-provider integration ecosystem

**Business Goal**

Onboard and operate additional attendance or ticketing providers predictably without changing Encore's core domain or weakening one provider's isolation from another.

**Domain Entities**

Planned provider-neutral concepts may include provider definition, organisation integration, scoped credential, external identity mapping, synchronization checkpoint, delivery receipt, and reconciliation run. TicketPal remains one provider implementation.

**API Contracts**

Define a provider contract covering authentication, capabilities, show/performance identity, invitation eligibility, idempotency, timestamps, pagination or batching, errors, versioning, deprecation, rate limits, and support ownership. Provider-specific adapters translate that contract into Encore services.

**Database Impact**

- Normalize integration and credential ownership when a second provider justifies the abstraction.
- Preserve provider namespaces in every external identity key.
- Store encrypted or externally managed credential references, capability configuration, checkpoints, and delivery receipts.
- Avoid generic metadata replacing explicit invariants required by the core domain.

**Events**

Core facts use Encore terminology. Provider-specific payloads remain at adapter boundaries. Event schemas require versions and must not expose credentials or unnecessary provider/customer data.

**Queues**

Each integration requires isolated queue capacity, concurrency limits, retry/backoff, circuit breaking, dead-letter handling, replay tooling, and reconciliation so one provider cannot starve another.

**Security**

- Scope credentials to one provider integration and minimum capability.
- Support rotation, revocation, signing, replay protection, audit, and incident isolation.
- Complete provider threat modelling and data-processing review before activation.

**Testing**

Provide adapter contract tests, provider sandbox fixtures, idempotency and replay tests, credential-scope tests, failure injection, rate-limit behavior, reconciliation, and cross-provider isolation tests.

**Documentation**

Maintain an integration onboarding standard, capability matrix, contract versions, credential runbook, provider-specific mapping guide, operational ownership, reconciliation procedures, and deprecation policy.

**Dependencies**

C01 production operations; C02 integration ownership; C03 review evidence rules; mature C04 ingestion and C05 invitation capabilities; accepted provider-neutral adapter design; commercial and data-protection agreements.

**Acceptance Criteria**

- A new provider can be added through a bounded adapter without provider-specific concepts entering core organisation, show, performance, invitation, or review models.
- Provider credentials and workloads are isolated and independently revocable.
- All adapters pass the same conformance, security, idempotency, and reconciliation suite.
- Contract changes follow documented versioning and deprecation rules.

## Capability delivery lifecycle

Before entering delivery, a significant capability must complete the Operating Principles decision framework: Strategic Review, Engineering Review, and Founder Approval.

Every capability increment moves through the following gates:

1. **Outcome definition** — business goal, measures, owner, scope, and exclusions are agreed.
2. **Domain design** — entities, invariants, ownership, lifecycle, and privacy classification are explicit.
3. **Contract design** — APIs and events have versioning, validation, idempotency, error, and compatibility rules.
4. **Architecture approval** — material decisions are captured in accepted or superseding ADRs.
5. **Operational design** — persistence, migration, queues, telemetry, capacity, recovery, and support are defined.
6. **Security review** — authentication, authorization, tenancy, secrets, abuse cases, retention, and threat mitigations are approved.
7. **Implementation and verification** — code, PostgreSQL migrations, automated tests, static checks, and performance evidence pass CI.
8. **Release readiness** — documentation, runbooks, rollback, alerts, ownership, and provider/customer communication are complete.
9. **Outcome review** — production measures are compared with the business goal and follow-up work is reprioritized.

## Definition of capability complete

A capability is complete only when:

- its acceptance criteria are met with evidence;
- current behavior is represented accurately in the domain and API references;
- architectural decisions are accepted and implemented;
- database integrity and rollback behavior are verified on PostgreSQL;
- tenant boundaries and security abuse cases are tested;
- events and jobs, when used, are idempotent, observable, recoverable, and documented;
- operational dashboards, alerts, runbooks, and owners exist;
- compatibility and deprecation responsibilities are explicit;
- no proposed behavior is described as already available.

## Deliberately uncommitted decisions

The roadmap does not yet select:

- a production hosting platform or analytical datastore;
- an external identity, email, messaging, monitoring, or secrets provider;
- a generic provider adapter interface before a second provider supplies concrete requirements;
- an event transport or outbox implementation;
- a widget rendering and distribution architecture;
- analytics metrics, retention periods, or minimum privacy cohorts;
- delivery dates or commercial commitments.

These decisions require evidence, product ownership, and architecture or security review. They must not be inferred from capability priority.
