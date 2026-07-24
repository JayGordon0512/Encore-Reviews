# Encore Engineering Implementation Plan

**Version:** 0.2

**Date:** 24 July 2026

**Owner:** Encore Reviews Engineering

**Status:** Proposed — governance approval required

## Purpose and Authority

This document is the proposed engineering implementation plan for the Encore Platform. It translates the strategic direction in [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md), [Platform Strategy](Platform-Strategy.md), the [Encore Product Blueprint](Encore-Product-Blueprint.md), the [Audience Journey](Audience-Journey.md), the governance model in the [Operating Principles](Operating-Principles.md), the constraints in the [Encore Reviews Platform Charter](Encore-Platform-Charter.md), and the findings in the [Vision Architecture Review](Vision-Architecture-Review.md) into an incremental technical strategy.

This plan has been reconciled at a strategic level with the authoritative vision documents. It remains proposed: it does not authorize implementation of capabilities, settle assumptions that the Product Blueprint marks for validation, or supersede an accepted Architecture Decision Record (ADR). Every significant initiative still requires Strategic Review, Engineering Review, and Founder Approval.

## Table of Contents

- [Implementation Principles](#implementation-principles)
- [Domain Model](#domain-model)
- [Bounded Contexts](#bounded-contexts)
- [Services](#services)
- [APIs](#apis)
- [Events](#events)
- [Data Model](#data-model)
- [Permissions](#permissions)
- [External Integrations](#external-integrations)
- [AI Services](#ai-services)
- [Suggested Milestones](#suggested-milestones)
- [Architecture Decision Records](#architecture-decision-records)
- [Implementation Governance](#implementation-governance)

## Implementation Principles

1. **Trust before intelligence.** No insight, recommendation, or campaign capability should be built on data whose provenance, consent, quality, and tenant boundary are unclear.
2. **Modular monolith before distributed services.** Preserve a single deployable while establishing explicit module ownership and contracts. Extract a service only when scale, isolation, availability, compliance, or team ownership provides evidence for doing so.
3. **Operational truth and analytical derivation remain separate.** Transactional records are authoritative. Analytics, features, predictions, and recommendations are derived, versioned, reproducible, and disposable.
4. **Provider-neutral core.** Provider terminology and credentials remain inside integration boundaries. Core domain objects use Encore identities and vocabulary.
5. **Consent is executable policy.** Consent, communication preferences, purpose restrictions, retention, and deletion must be enforceable in application, analytics, and AI workflows.
6. **Identity grants access; verification grants authority.** Accounts, authentication, and membership must not create trusted contribution rights without explicit verified authority under ADR-015.
7. **Secure tenant isolation.** Every command, query, event, export, model input, and cache key must carry an authenticated principal and an explicit tenant or public scope.
8. **Asynchronous by design, not by default.** User-critical invariants commit synchronously. Slow or independently recoverable reactions use durable asynchronous processing with observable retries.
9. **AI is governed software.** Models require owners, versions, evaluations, release gates, monitoring, explanations appropriate to impact, and a deterministic fallback.
10. **Measure before decomposing.** Capacity, latency, throughput, freshness, availability, recovery, accuracy, diversity, and cost targets guide architectural change.
11. **Every material decision is traceable.** Product capability, policy, ADR, API contract, data migration, test evidence, operational runbook, and release record remain linked.

## Domain Model

### Existing authoritative core

The current core remains valid for verified-review delivery:

```text
Organisation
├── Users
├── Shows
│   └── Performances
│       ├── Review invitations
│       └── Reviews
└── Venues

Reviewer ── authors ──> Review
Provider delivery ── synchronizes ──> Show / Performance / Invitation
```

Existing invariants remain binding: organisation-rooted tenancy, performance-level verification, single-use invitation evidence, moderation before publication, provider-independent UUIDs, and idempotent provider synchronization.

### Proposed target concepts

The following model is a capability map, not approval to add all entities immediately.

| Domain area | Proposed concepts | Principal invariants |
| --- | --- | --- |
| Organisation governance | `Organisation`, `OrganisationRelationship`, `Membership`, `RoleAssignment`, `Entitlement` | Access is explicitly granted; shared productions do not imply unrestricted tenant access |
| Catalogue | `Production`, `Show`, `Performance`, `Venue`, `Genre`, `AccessibilityAttribute` | Public catalogue identity is provider-independent; scheduling and presentation are distinguishable from ownership |
| Provider integration | `Provider`, `ProviderConnection`, `ProviderCredential`, `ExternalEntityLink`, `InboundDelivery`, `ReconciliationRun` | Credentials and external IDs are scoped to one connection; replay and reconciliation converge safely |
| Attendance and eligibility | `AttendanceEvidence`, `ReviewEligibility`, `ReviewInvitation`, `DeliveryAttempt` | Evidence is attributable to source and performance; one eligibility grant cannot create unintended duplicate reviews |
| Audience identity | `AudienceAccount`, `AudienceProfile`, `ExternalAudienceIdentity`, `IdentityLink`, `ConsentGrant`, `CommunicationPreference` | Identity linking is explicit and reversible; purpose and consent are versioned; deletion propagates |
| Review trust | `Reviewer`, `Review`, `Verification`, `ModerationDecision`, `Appeal`, `PublicationState` | Verification, content, moderation, and publication are separate concerns with complete history |
| Discovery | `Favourite`, `WatchlistItem`, `DiscoveryPreference`, `Recommendation`, `RecommendationImpression`, `AudienceInteraction` | Paid influence is distinguishable; recommendations retain reason and model provenance |
| Intelligence | `MetricDefinition`, `AudienceSegment`, `Insight`, `Benchmark`, `AggregateSnapshot`, `DataQualityAssessment` | Derived outputs identify source period, cohort, calculation version, quality, and disclosure threshold |
| Engagement | `Campaign`, `CampaignAudience`, `Message`, `Delivery`, `Suppression`, `FrequencyPolicy` | Eligibility is checked at send time; withdrawal and suppression override campaign membership |
| AI governance | `AIUseCase`, `Model`, `ModelVersion`, `DatasetVersion`, `Evaluation`, `InferenceRecord`, `ModelIncident` | Every production inference maps to an approved use case, model version, evaluation, owner, and policy |

### Required domain validation

Before changing the current ownership model, validate co-productions, tours, venue hires, promoters, festivals, shared administration, multiple providers, organisation mergers, rights transfers, and cross-organisation reporting. `Production`, `Show`, and `Event` terminology must be agreed with product stakeholders rather than inferred from the current schema.

**ADR required:** evolution of `Organisation` as tenancy root; multi-organisation relationships; audience identity and consent model; moderation and publication authority.

## Bounded Contexts

The target architecture should begin as modules in the Laravel application. A bounded context is a domain and ownership boundary, not automatically a separate process or repository.

| Context | Responsibilities | Owns | Does not own |
| --- | --- | --- | --- |
| Identity and Access | Authentication, sessions, MFA, service principals, role assignments | Principal and credential lifecycle | Organisation business data or audience preferences |
| Organisation Governance | Organisations, memberships, entitlements, support access, tenant policy | Organisation access relationships | Catalogue, reviews, provider payloads |
| Catalogue and Programming | Productions, shows, performances, venues, public metadata | Encore catalogue identity and schedule | Ticket orders, audience profiles, recommendations |
| Provider Integration | Provider connections, authentication, ingress, mapping, replay, reconciliation | External identities and delivery state | Core catalogue or audience truth |
| Attendance and Eligibility | Attendance evidence, review eligibility, invitations, delivery lifecycle | Eligibility and invitation state | Review content and moderation |
| Audience | Accounts, profiles, preferences, consent, identity linking, data rights | Audience-declared and governed identity state | Provider credentials, organisation analytics |
| Review Trust | Review submission, verification provenance, moderation, appeals, publication | Review and decision history | Campaign delivery, general audience profiles |
| Discovery | Search, favourites, watchlists, ranking, recommendation serving | Discovery state and recommendation evidence | Model training or organisation campaign policy |
| Audience Intelligence | Metrics, cohorts, aggregates, benchmarks, insights, data quality | Governed derived intelligence | Operational source records |
| Engagement | Segmentation requests, campaigns, channel policy, delivery and suppression | Campaign intent and communication evidence | Audience consent source of truth |
| AI Platform | Model registry, feature contracts, evaluation, inference gateway, monitoring | Model lifecycle and inference evidence | Product policy or raw operational ownership |
| Platform Assurance | Audit evidence, observability, retention orchestration, incident and recovery controls | Cross-cutting operational evidence | Business decisions inside other contexts |

### Context interaction rules

- Contexts reference another context's entities by stable Encore ID; they do not write another context's tables directly through controllers.
- Commands enter the owning context through an application service.
- Synchronous queries are acceptable inside the modular monolith when ownership remains explicit.
- Cross-context reactions use versioned domain events only after event-delivery semantics are approved.
- Intelligence and AI consume governed projections, not unrestricted access to operational tables.
- Engagement asks Audience for current communication eligibility at execution time; a cached segment never overrides current consent or suppression.

**ADR required:** modular-monolith boundaries and dependency rules; criteria for later service extraction; cross-context consistency model.

## Services

### Application and domain services

| Service | Context | Responsibilities | Consistency |
| --- | --- | --- | --- |
| `OrganisationAccessService` | Organisation Governance | Membership, role, entitlement, activation, support-access decisions | Synchronous transaction |
| `CatalogueService` | Catalogue | Catalogue commands and lifecycle invariants | Synchronous transaction |
| `PerformanceSyncService` | Provider Integration / Catalogue | Existing provider performance convergence through an explicit boundary | Synchronous transaction |
| `ProviderIngestionService` | Provider Integration | Authenticate, register, validate, map, dispatch, and classify inbound deliveries | Synchronous acceptance; async processing where approved |
| `ReconciliationService` | Provider Integration | Compare provider and Encore state and propose or apply repairs | Asynchronous and idempotent |
| `EligibilityService` | Attendance and Eligibility | Interpret evidence and issue/cancel review eligibility | Synchronous authority |
| `InvitationService` | Attendance and Eligibility | Token issuance, expiry, consumption coordination, and delivery request | Synchronous state; async delivery |
| `AudienceIdentityService` | Audience | Account lifecycle, explicit identity link/merge/split, and recovery | Synchronous transaction |
| `ConsentService` | Audience | Record versioned grants and withdrawals; answer purpose-specific eligibility | Synchronous authority |
| `DataRightsService` | Audience / Platform Assurance | Export, correction, deletion, restriction, and downstream propagation | Durable workflow |
| `ReviewSubmissionService` | Review Trust | Validate evidence, create reviewer/review, consume invitation atomically | Synchronous transaction |
| `ModerationService` | Review Trust | Decision transitions, reasons, appeals, and publication state | Synchronous transaction |
| `SearchService` | Discovery | Query public catalogue and governed search index | Read optimized |
| `RecommendationService` | Discovery | Request, filter, explain, and record recommendations | Online read with fallback |
| `InsightService` | Audience Intelligence | Retrieve governed metrics, cohorts, benchmarks, and quality metadata | Read optimized |
| `CampaignService` | Engagement | Author, approve, schedule, and stop campaigns | Synchronous command |
| `AudienceSelectionService` | Engagement | Resolve a segment under current policy and disclosure rules | Async materialization |
| `DeliveryOrchestrator` | Engagement | Re-check eligibility, send through channel adapters, retry, and suppress | Asynchronous and idempotent |
| `AIInferenceGateway` | AI Platform | Authorize use case, minimize input, route model, record provenance, enforce fallback | Online or batch by use case |
| `ModelEvaluationService` | AI Platform | Evaluate quality, safety, bias, diversity, drift, and release gates | Batch |
| `AuditService` | Platform Assurance | Append allowlisted privileged-action evidence | Same transaction or transactional outbox |

These are logical services. They should be implemented as focused application/domain services in the modular monolith unless an approved ADR assigns a separate deployment boundary.

### Candidate future deployables

Only the following have plausible independent scaling or isolation drivers:

- provider ingestion workers;
- notification delivery workers;
- search indexing and query infrastructure;
- analytics transformation and warehouse workloads;
- recommendation and AI inference serving;
- data-rights and retention orchestration.

Extraction requires measured need, a clear owner, independent service objectives, contract tests, observability, deployment automation, and a migration/rollback plan.

**ADR required:** service-layer conventions; queue and worker operating model; service-extraction criteria; AI inference isolation.

## APIs

### API families

| API family | Principal | Style | Purpose |
| --- | --- | --- | --- |
| Public Discovery API | Anonymous or audience account | Versioned REST query API | Shows, performances, venues, approved reviews, search, public recommendation entry points |
| Audience API | Audience account or scoped invitation | Versioned REST command/query API | Profile, preferences, consent, favourites, watchlist, history, review submission, data rights |
| Organisation API | Organisation user | Versioned REST command/query API | Catalogue, reviews, moderation, insights, segments, campaigns, exports |
| Encore Operations API | Privileged Encore principal | Versioned REST command/query API | Organisation lifecycle, support, policy, audit, integration operations |
| Provider API | Provider connection principal | Versioned REST/webhook ingress | Catalogue, performance, attendance, eligibility, delivery status, reconciliation |
| Internal Inference API | Workload identity | Typed synchronous/batch contract | Approved recommendation, classification, summarization, and prediction use cases |

### Contract standards

- Introduce URI or media-type versioning before the next breaking external contract.
- Publish OpenAPI specifications for external and organisation APIs.
- Use stable Encore IDs, UTC timestamps, cursor pagination, explicit filtering, bounded payloads, and consistent problem details.
- Require idempotency keys for retryable commands and signed event identity for provider webhooks.
- Return correlation IDs and document rate limits, retry semantics, compatibility, and deprecation windows.
- Keep bulk operations bounded and asynchronous when processing cannot complete within the synchronous service objective.
- Prevent caller-supplied organisation IDs from establishing authorization scope.
- Apply field-level response policies for personal, inferred, sensitive, or cross-organisation data.
- Define webhook subscriptions and outbound signing separately from internal domain events.

### Compatibility strategy

The current unversioned TicketPal API remains a legacy contract until migrated under the approved provider specification. A compatibility adapter should translate legacy payloads into provider-neutral commands while new integrations use the versioned contract. Breaking changes require an announced support window, consumer verification, metrics, and rollback.

**ADR required:** public and provider API versioning; error format; idempotency semantics; webhook delivery; schema and deprecation governance.

## Events

### Event architecture

Adopt domain events only after the proposed event-driven and queue ADRs are accepted or superseded. The recommended baseline is:

1. The owning context commits aggregate state and an outbox record in one PostgreSQL transaction.
2. An outbox publisher delivers events with at-least-once semantics.
3. Consumers are idempotent and retain processing checkpoints or inbox records where effects are material.
4. Event schemas are versioned, additive where possible, and registered with owners and retention rules.
5. Events contain stable IDs and minimum necessary attributes; personal data is referenced or tokenized rather than copied broadly.
6. Correlation and causation IDs connect provider delivery, command, event, job, inference, and audit evidence.
7. Replay is controlled, authorized, observable, and tested.

Exactly-once delivery should not be claimed. Exactly-once effective business outcomes should be achieved through uniqueness, transactions, and idempotent consumers.

### Proposed event catalogue

| Context | Events |
| --- | --- |
| Organisation Governance | `OrganisationActivated`, `OrganisationDeactivated`, `MembershipChanged`, `EntitlementChanged` |
| Catalogue | `ShowPublished`, `ShowArchived`, `PerformanceScheduled`, `PerformanceChanged`, `PerformanceCancelled` |
| Provider Integration | `ProviderDeliveryAccepted`, `ProviderDeliveryRejected`, `ProviderEntitySynchronized`, `ReconciliationCompleted` |
| Attendance and Eligibility | `AttendanceEvidenceRecorded`, `ReviewEligibilityGranted`, `ReviewInvitationIssued`, `ReviewInvitationConsumed`, `ReviewInvitationExpired` |
| Audience | `AudienceAccountCreated`, `IdentityLinked`, `ConsentGranted`, `ConsentWithdrawn`, `CommunicationPreferenceChanged`, `AudienceDeletionRequested` |
| Review Trust | `ReviewSubmitted`, `ReviewApproved`, `ReviewRejected`, `ReviewAppealed`, `ReviewPublicationChanged` |
| Discovery | `FavouriteAdded`, `WatchlistChanged`, `RecommendationServed`, `RecommendationOutcomeRecorded` |
| Intelligence | `AggregatePublished`, `InsightGenerated`, `DataQualityFailed` |
| Engagement | `CampaignApproved`, `CampaignScheduled`, `MessageDeliveryRequested`, `MessageDelivered`, `MessageSuppressed`, `CampaignStopped` |
| AI Platform | `ModelVersionApproved`, `ModelVersionDeployed`, `ModelDriftDetected`, `ModelSuspended`, `AIIncidentOpened` |

Audience interaction telemetry is not automatically a domain event. Collection requires an approved purpose, a defined schema, retention, sampling, consent enforcement, and protection against using telemetry as an ungoverned shadow profile.

**ADR required:** transactional outbox and delivery guarantees; schema evolution; event privacy; replay and retention; telemetry governance.

## Data Model

### Storage strategy

| Store | Purpose | Authority |
| --- | --- | --- |
| PostgreSQL operational database | Aggregates, permissions, consent, provider state, reviews, moderation, audit linkage | System of record for transactional state |
| Redis or equivalent | Queues, bounded cache, rate limits, locks where database locking is unsuitable | Never sole authority for business state |
| Object storage | Images, exports, model/evaluation artifacts, large immutable evidence where approved | Metadata and access policy remain in governed records |
| Search index | Public catalogue and approved review discovery | Rebuildable projection |
| Analytics warehouse/lakehouse | Conformed events, aggregates, cohort metrics, model-ready governed data | Derived analytical store with lineage |
| Feature store, if justified | Reusable online/offline recommendation features | Derived, versioned, consent-filtered; not audience truth |
| Model registry | Model versions, artifacts, evaluations, approvals, rollback state | Authority for production model lifecycle |

### Operational modelling rules

- Use UUIDs for domain entities and explicit foreign keys for ownership and provenance.
- Add organisation scope to owned tables where it improves enforceability and query safety, while preventing contradictory redundant ownership.
- Represent many-to-many ecosystem relationships explicitly rather than weakening tenant rules.
- Model state transitions with constrained values and immutable decision history.
- Store consent as append-only grants and withdrawals linked to policy/purpose versions; maintain a current projection for fast enforcement.
- Store external identities as provider-connection-scoped mappings with uniqueness and effective dates.
- Separate attendance evidence from review eligibility and invitation delivery.
- Link each review to the eligibility or invitation that authorized it.
- Use keyed, versioned pseudonymous identifiers where lookup without plaintext is required; manage keys independently from application data.
- Record provenance, calculation version, cohort size, quality status, and disclosure policy on derived insight.
- Partition only when measured size and access patterns justify it; likely candidates are deliveries, audit evidence, interaction events, inference records, and message delivery records.

### Analytical modelling rules

- Define conformed dimensions for organisation, production/show, performance, venue, time, provider, audience pseudonym, and consent purpose.
- Treat attendance, review, moderation, recommendation impression/outcome, and message delivery as distinct facts.
- Use slowly changing dimensions where historical interpretation must survive source updates.
- Maintain a metric catalogue with definition, owner, grain, filters, quality tests, and version.
- Enforce minimum cohort and disclosure thresholds before organisation-facing output.
- Retain lineage from source record and consent purpose through transformation, feature, model, and insight.
- Propagate correction, restriction, and deletion to projections, exports, features, and training datasets according to policy.

### Migration strategy

Use expand/migrate/contract changes, backfills with checkpoints, dual-read or dual-write only for bounded transitions, reconciliation reports, and rehearsed rollback. Schema presence must not be represented as a delivered capability. High-volume backfills and index creation require production-safe migration plans.

**ADR required:** operational versus analytical data boundary; audience identifier protection; consent storage; retention and deletion propagation; search store; model/feature storage; tenant-enforcement strategy.

## Permissions

### Authorization model

Adopt RBAC for understandable role assignment combined with resource and attribute checks for organisation, relationship, purpose, data sensitivity, and action. Deny by default. Authorization is performed server-side at the owning context; UI visibility is not a security control.

| Principal | Illustrative permissions | Explicit restrictions |
| --- | --- | --- |
| Anonymous audience | Read public catalogue and approved content; use valid invitation | No private profile, organisation data, or unpublished review access |
| Audience member | Manage own profile, consent, preferences, favourites, watchlist, reviews, export/deletion request | No other audience identity or organisation analytics access |
| Organisation owner/admin | Manage membership and organisation settings within entitlement | No cross-organisation access or unrestricted Encore policy changes |
| Organisation catalogue manager | Manage authorized shows, performances, and venues | No audience identity or campaign access unless separately granted |
| Organisation moderator | View and decide reviews for authorized catalogue scope | No campaign targeting or raw provider credentials |
| Organisation analyst | View thresholded, authorized insights and exports | No direct audience identity, small-cohort, or unrelated organisation data |
| Organisation campaign manager | Create campaigns from permitted segments | No raw segment membership export unless explicitly approved |
| Encore support | Time-bound, audited read access to approved support views | No impersonation or mutation by default |
| Encore platform admin | Govern organisations, policies, integrations, and platform operations | Break-glass and high-risk actions require stronger controls |
| Provider principal | Write/read only the approved contract for its scoped connection | Cannot select arbitrary organisation ownership or access audience profiles |
| Service principal | Minimum context-specific machine permissions | No interactive login or broad shared credentials |
| AI workload principal | Access approved minimized feature view for one use case | No unrestricted operational database or cross-tenant raw data access |

### Mandatory controls

- MFA for privileged users and policy for customer administrators.
- Short-lived service credentials, rotation, revocation, and workload identity where available.
- Database constraints supporting role and organisation invariants.
- Field-level authorization and purpose checks for audience and inferred data.
- Step-up authorization and dual control for high-impact exports, model activation, bulk messaging, credential changes, and break-glass access.
- Immutable evidence for privileged access, export, consent-policy change, model release, and campaign approval.
- Automated role/action/tenant matrix tests and negative cross-tenant tests.

**ADR required:** role and entitlement model; tenant enforcement including PostgreSQL row-level security evaluation; privileged access and break-glass policy; machine identity.

## External Integrations

### Integration architecture

All integrations pass through an anti-corruption boundary containing authentication, validation, provider mapping, idempotency, rate limiting, observability, and error classification. Integration adapters translate between provider contracts and provider-neutral application commands.

| Integration class | Direction | Required controls |
| --- | --- | --- |
| Ticketing and attendance providers | Inbound and reconciliation | Per-connection credentials, signed events, stable IDs, scope, idempotency, replay protection, reconciliation, versioning |
| Email/SMS/push providers | Outbound plus delivery webhooks | Consent recheck, suppression, sender policy, token secrecy, retries, delivery evidence, provider failover policy |
| Identity providers | Interactive authentication | OIDC/SAML as selected, MFA, account-linking controls, session revocation, assurance levels |
| Maps/geocoding | Request/response or batch | Address minimization, caching/licensing rules, accuracy and correction handling |
| Search infrastructure | Internal projection | Rebuildability, tenant/public filters, approved-content enforcement, freshness monitoring |
| Analytics/BI tools | Governed outbound/query | Row and field policy, cohort thresholds, export audit, revocation, no uncontrolled extracts |
| AI model providers | Request/response or batch | Data-processing terms, no-training controls, regional processing, redaction, retention, version pinning, fallback |
| Customer webhooks | Outbound | Subscription authorization, signing, retry, replay defense, secret rotation, data minimization |

### Provider lifecycle

1. Register provider and scoped connection.
2. Approve contract version, data purposes, credential method, and organisation scope.
3. Complete conformance and security testing.
4. Activate with rate, error, and reconciliation monitoring.
5. Rotate credentials and review access periodically.
6. Suspend safely on compromise or contract breach.
7. Disconnect with defined retention, mapping, and recovery semantics.

The current global TicketPal secret should be migrated to scoped, rotatable credentials before onboarding multiple providers or organisations under independent trust boundaries.

**ADR required:** provider adapter model; credential and connection model; provider API versioning; reconciliation; communication provider selection; third-party AI and data-processing policy.

## AI Services

### Candidate services and delivery order

| AI capability | Product purpose | Minimum inputs | Required safeguards | Readiness |
| --- | --- | --- | --- | --- |
| Review theme classification | Help organisations understand recurring topics | Approved review text and controlled taxonomy | Quality evaluation, multilingual support, provenance, no unsupported claims | First candidate after analytics governance |
| Sentiment summarization | Summarize aggregate audience feedback | Thresholded approved-review cohort | Source links, cohort size, uncertainty, human correction, hallucination testing | Pilot only |
| Similar-show recommendation | Improve discovery | Catalogue metadata and aggregate review signals | Diversity, cold start, paid influence separation, explanation, fallback | Early recommendation candidate |
| Personalized recommendation | Match audiences to relevant performances | Explicit preferences and consented interactions | Opt-out, minimization, diversity, sensitive-trait controls, user feedback | After audience identity and consent |
| Audience insight generation | Turn governed metrics into suggested actions | Versioned aggregate metrics | Traceability, no invented facts, confidence, human review | After trusted analytics |
| Attendance/demand forecasting | Support planning | Historical aggregate attendance, schedule, context | Error bounds, drift, seasonality, no individual targeting | Later-stage pilot |
| Campaign audience suggestion | Improve relevance | Approved segment definitions and consented attributes | Human approval, exclusion rules, proxy-bias testing, frequency limits | High risk; later stage |
| Generative campaign assistance | Draft content | Organisation prompt, approved catalogue facts, brand policy | Human approval, factual grounding, content safety, rights review | Optional pilot |

### AI platform components

- **Use-case registry:** purpose, owner, affected users, risk tier, lawful basis, approved inputs/outputs, fallback, and review date.
- **Feature contracts:** typed, versioned, lineage-aware, consent-filtered inputs shared between training and serving.
- **Model registry:** artifact, code, dataset, prompt/configuration, evaluation, approval, deployment, and rollback versions.
- **Inference gateway:** authenticates workload, enforces use-case policy, minimizes/redacts input, selects approved version, captures provenance, applies timeouts and fallback.
- **Evaluation harness:** relevance, accuracy, calibration, hallucination, toxicity, bias, diversity, privacy leakage, robustness, latency, and cost tests.
- **Monitoring:** quality and input drift, cohort performance, exposure distribution, safety violations, latency, errors, spend, and user correction signals.
- **Human oversight:** approve high-impact outputs, correct source data, challenge decisions, suspend models, and investigate incidents.

### AI release gates

No AI capability reaches production until:

1. its use case and prohibited uses are approved;
2. data provenance, rights, consent, retention, and vendor processing are documented;
3. baseline non-AI behaviour and deterministic fallback exist;
4. acceptance thresholds and representative evaluation sets are approved;
5. privacy, security, bias, accessibility, and abuse reviews pass;
6. output disclosure and explanation are appropriate to impact;
7. monitoring, rollback, incident ownership, and model suspension are operational;
8. an experiment demonstrates measurable value without unacceptable trust harm.

Model training and online inference must not read unrestricted production tables. Raw reviews, personal attributes, and cross-tenant data require use-case-specific governed access.

**ADR required:** AI governance and risk classification; build-versus-buy and vendor data policy; model registry and inference gateway; recommendation objectives; feature/data lineage; human oversight and explanation standards.

## Suggested Milestones

Milestones are capability gates, not fixed-duration sprints. Detailed dates require the approved Product Blueprint, staffing, service objectives, and production constraints.

### Milestone 0 — Blueprint reconciliation and decision baseline

**Outcome:** The engineering plan is traceable to approved product outcomes and audience journeys.

- Approve the Product Blueprint and Audience Journey.
- Resolve terminology, value exchange, commercial influence, moderation authority, and organisation/audience ownership.
- Define initial scale, availability, recovery, data freshness, privacy, jurisdiction, and accessibility requirements.
- Approve the strategic-document hierarchy and initial ADR backlog.

**Exit gate:** Every proposed Milestone 1–3 capability has a product owner, intended user outcome, non-goals, data purpose, success measure, and decision owner.

### Milestone 1 — Production trust and platform assurance

**Outcome:** The current verified-review platform has a secure, observable, recoverable production foundation.

- Close production security, secrets, MFA, rate limiting, audit retention, backup/restore, monitoring, alerting, incident, and CI/CD gaps.
- Define service objectives and capacity baselines.
- Complete API versioning and provider credential decisions.
- Accept, supersede, or reject event and queue ADRs.

**Exit gate:** Recovery is exercised; tenant and permission tests pass; privileged access is attributable; operational alerts and owners exist.

### Milestone 2 — Provider-neutral ingestion and invitation lifecycle

**Outcome:** Multiple scoped provider connections can synchronize catalogue and evidence safely.

- Introduce provider connections, scoped credentials, adapter boundary, contract conformance, and reconciliation.
- Separate attendance evidence, review eligibility, invitation, and delivery attempt.
- Add queue-backed delivery with idempotent retry and observable lifecycle.
- Migrate TicketPal through compatibility and rollback stages.

**Exit gate:** Duplicate, concurrent, delayed, and reordered delivery converges; credential compromise is bounded; reconciliation repairs tested drift.

### Milestone 3 — Audience identity, consent, and rights

**Outcome:** Audience members can receive clear value while controlling profile, preferences, communication, and data use.

- Implement optional audience accounts, explicit provider identity linking, consent ledger, preference centre, and communication suppression.
- Implement export, correction, withdrawal, restriction, and deletion workflows with downstream propagation.
- Upgrade pseudonymous identifier protection and key management.

**Exit gate:** Every audience-data use maps to an approved purpose; rights workflows meet stated completion targets; silent cross-provider identity merging is impossible.

### Milestone 4 — Review governance and public discovery

**Outcome:** Review trust is independently governable and audiences can discover the public catalogue effectively.

- Add immutable moderation history, policy, reason standards, appeals, abuse signals, and transparency measures.
- Introduce versioned public/audience APIs, search projection, favourites, watchlist, and accessibility-aware catalogue fields.
- Measure representativeness and moderation bias.

**Exit gate:** Publication decisions are attributable and appealable; only authorized approved content is indexed; search meets freshness and latency targets.

### Milestone 5 — Governed audience intelligence

**Outcome:** Organisations receive reliable, thresholded, explainable insight without direct access to audience identity.

- Establish event/outbox delivery, analytical store, metric catalogue, lineage, quality tests, cohort thresholds, and tenant-safe exports.
- Deliver descriptive insight before prediction: sentiment distribution, recommendation rate, attendance patterns, geographic reach where permitted, and repeat engagement.
- Pilot aggregate review-theme classification under AI release gates.

**Exit gate:** Every metric is reproducible and versioned; small cohorts are protected; deletion and consent changes propagate; insight quality is visible.

### Milestone 6 — Discovery recommendations

**Outcome:** Audiences receive useful, diverse recommendations with explanations and control.

- Establish model registry, feature contracts, inference gateway, evaluation harness, and non-AI fallback.
- Begin with similar-show recommendations, then opt-in personalization.
- Record impressions and outcomes under approved telemetry policy.
- Evaluate relevance, diversity, novelty, cold start, regional fairness, and exposure concentration.

**Exit gate:** Recommendation performance exceeds the approved baseline without violating trust, diversity, latency, privacy, or cost thresholds.

### Milestone 7 — Governed audience engagement

**Outcome:** Organisations can communicate with eligible audiences through controlled, auditable campaigns.

- Implement entitlements, segment requests, campaign approval, consent-at-send checks, frequency policy, suppression, delivery orchestration, and reporting.
- Keep raw audience identity and unrestricted segment membership unavailable to organisations by default.
- Distinguish marketing, service messages, organic recommendation, and paid influence.

**Exit gate:** Withdrawal and suppression are honored before send; bulk actions are approved and auditable; complaint, delivery, and frequency thresholds are met.

### Milestone 8 — Predictive and generative intelligence

**Outcome:** Higher-risk AI capabilities deliver proven value under mature governance.

- Pilot demand forecasting, generated insight narratives, campaign suggestions, and other approved use cases separately.
- Require human approval for creative, commercial, or audience-impacting outputs.
- Expand only after sustained quality, bias, drift, incident, and trust evidence.

**Exit gate:** Each use case independently passes its release criteria and can be suspended without degrading core review, discovery, or reporting capabilities.

## Architecture Decision Records

The following decisions require ADRs before implementation. Numbers are suggested placeholders following the current register and must be assigned by the ADR owner.

| Suggested ADR | Decision | Required by | Existing relationship |
| --- | --- | --- | --- |
| ADR-015 | Modular monolith bounded contexts and dependency rules | Milestone 1 | Extends ADR-005 |
| ADR-016 | Organisation root evolution and multi-organisation resource relationships | Milestone 0/2 | Must preserve or supersede ADR-001 deliberately |
| ADR-017 | Audience identity, provider linking, consent, and erasure model | Milestone 3 | New strategic domain decision |
| ADR-018 | RBAC/ABAC entitlement model and tenant enforcement | Milestone 1/3 | Extends or supersedes ADR-013 where necessary |
| ADR-019 | Transactional outbox, event envelope, delivery, replay, and schema evolution | Milestone 1/5 | Accepts, revises, or supersedes ADR-007 |
| ADR-020 | Queue technology and worker operating model | Milestone 1/2 | Accepts, revises, or supersedes ADR-008 |
| ADR-021 | External API versioning, idempotency, errors, and deprecation | Milestone 1/2 | Extends ADR-004 and provider specifications |
| ADR-022 | Provider connection, scoped credential, adapter, and reconciliation model | Milestone 2 | Extends ADR-006, ADR-010, ADR-011, and ADR-014 |
| ADR-023 | Review moderation authority, history, appeals, and publication policy boundary | Milestone 4 | Extends current trust model |
| ADR-024 | Search engine and rebuildable discovery projection | Milestone 4 | New infrastructure decision |
| ADR-025 | Operational/analytical data separation, lineage, and deletion propagation | Milestone 5 | New data-platform decision |
| ADR-026 | Pseudonymous identifier protection and cryptographic key lifecycle | Milestone 3 | Extends privacy/security baseline |
| ADR-027 | AI governance, risk classification, registry, evaluation, and inference gateway | Milestone 5/6 | New AI platform decision |
| ADR-028 | Recommendation objectives, diversity, explainability, telemetry, and commercial influence | Milestone 6 | New product/algorithm decision |
| ADR-029 | Campaign consent enforcement, segmentation boundary, and delivery orchestration | Milestone 7 | New engagement decision |
| ADR-030 | Production service objectives, observability, resilience, backup, and recovery | Milestone 1 | Formalizes platform assurance |
| ADR-031 | Third-party data processor and AI model provider selection criteria | Before vendor adoption | New procurement and data-governance decision |

Each ADR should state context, decision, considered alternatives, consequences, privacy/security impact, operational impact, migration, rollback, cost, ownership, and evidence. Product policy questions must be resolved outside an ADR before architecture encodes them.

## Implementation Governance

### Definition of ready

A capability is ready for engineering design only when it has:

- an approved user and business outcome;
- defined actors, journeys, failure paths, and non-goals;
- domain owner and bounded context;
- data purpose, classification, lawful basis, retention, and rights behavior;
- threat model and tenant boundary;
- measurable functional and quality acceptance criteria;
- required ADRs accepted;
- external contract and migration impact identified.

### Definition of done

A capability is complete only when it includes:

- implemented invariants and database constraints;
- authorization and negative tenant tests;
- API/event contract tests and compatibility evidence;
- privacy, security, accessibility, and abuse-case verification;
- telemetry, service objectives, dashboards, alerts, and runbooks;
- migration, rollback, reconciliation, retention, and deletion behavior;
- updated architecture, domain, API, operations, roadmap, and release documentation;
- measured product outcome and an owner for post-release review.

### Review cadence

- Review milestone readiness before funding or sprint decomposition.
- Review ADR status and architectural risks at every milestone boundary.
- Review security, privacy, data, and AI risks before production exposure and after material scope changes.
- Review scale thresholds and service objectives quarterly once production usage is measurable.
- Reconcile this plan with every approved Product Blueprint revision without editing the Manifesto to fit implementation convenience.
