# Encore Reviews Platform Charter

- Status: Authoritative
- Owner: Encore Reviews engineering
- Last verified: 15 July 2026

## Mission

Encore Reviews is an independent audience review platform for live performance. Its mission is to help audiences make informed decisions and help live-event organisations understand verified audience feedback without making Encore dependent on any single ticketing provider.

## Platform mandate

The platform must:

- connect review eligibility to evidence for a specific performance;
- preserve Encore-owned identities independently of provider identifiers;
- publish only moderated audience feedback;
- isolate each organisation's operational and review data;
- support provider synchronization without allowing provider terminology to define the core domain;
- make repeated provider deliveries safe through idempotent synchronization;
- provide Encore administrators with controlled support access.

## Root ownership model

`Organisation` is the root ownership entity. It represents a theatre company, venue, festival, dance school, college, touring company, comedy promoter, music organisation, or another live-event operator.

The implemented ownership hierarchy is:

```text
Organisation
├── Users
├── Shows
│   └── Performances
│       ├── Review invitations
│       └── Reviews
└── Venues
```

TicketPal customers are organisations when their data enters Encore. TicketPal is an integration, not an ownership concept.

## Stakeholders

### Audiences

Audience members need relevant, trustworthy feedback and a straightforward way to submit a review when invited.

### Organisations

Organisation administrators need access to their own shows, review activity, scores, and moderation queue without access to another organisation's data.

### Encore operations

Encore super administrators need to create and manage organisations and users, assign imported shows, and inspect organisation dashboards for support. Support inspection is read-only and does not impersonate customer users.

### Provider integrations

Providers supply event, performance, attendance, booking, or ticket context through explicit integration boundaries. They do not own Encore's domain records or public review policy.

## Trust principles

### Identity and authority remain separate

Identity establishes who a participant is and which membership or administrative capabilities they may access. It does not automatically authorize trusted audience contribution.

Authority to submit a review derives from explicit verification and a valid review invitation for the relevant performance. See [ADR-015](../02-ADR/ADR-015-authority-through-verification.md).

### Performance-level verification

Review invitations and reviews attach to a performance rather than only to a show. This connects eligibility to a specific occurrence, time, and potentially venue.

### Single-use invitation evidence

Review submission requires a valid, unused, unexpired invitation token. Where an invitation contains an email hash, the submitted email must match.

### Moderation before publication

New reviews enter a pending state. Only approved reviews appear publicly or contribute to scores, counts, and recommendation rates.

### Pseudonymized identifiers

Encore stores hashes of invitation tokens and normalized reviewer email addresses. This reduces direct exposure but is pseudonymization rather than guaranteed anonymity.

### Explicit tenant isolation

Customer administration queries and mutations must scope data to the signed-in user's organisation. Any new organisation-sensitive capability must define and test its tenant boundary.

## Provider principles

- Encore identities are UUIDs and remain distinct from provider IDs.
- Provider identities are stored as integration metadata.
- Provider-specific routes and authentication remain outside the core domain language.
- Show and performance deliveries use stable provider keys and idempotent upserts.
- A provider performance cannot silently move between Encore shows.
- Performance timestamps are normalized to UTC at ingestion.
- New providers require deliberate contracts; the current application does not claim a generic adapter framework.

## Current platform capabilities

The authoritative current baseline includes:

- public show discovery pages and approved review display;
- Encore scores and recommendation rates;
- invitation-based audience review submission;
- TicketPal show, performance, and invitation APIs;
- organisation-scoped customer dashboards;
- review approval and rejection;
- Encore organisation and user administration;
- show assignment and read-only support views;
- Laravel Sail and PostgreSQL development operation;
- automated feature testing, formatting, and production asset builds.

## Current exclusions

The platform does not currently provide:

- embeddable review widgets;
- analytics products or exports;
- automated invitation email delivery;
- a generic provider adapter framework;
- integrations other than TicketPal;
- public show search or filtering;
- self-service registration, password reset, or organisation onboarding;
- scheduled or queued end-to-end provider synchronization;
- administrative audit history;
- production monitoring, backup, or incident-response automation.

These exclusions must not be represented as implemented functionality. Future direction is maintained in the engineering roadmap.

## Engineering principles

### Code and tests are executable truth

The handbook is authoritative guidance, but observable application behavior and executable tests are the final authority when a discrepancy is discovered. Documentation must then be corrected in the same change as the behavior or decision.

### Thin delivery boundaries

HTTP controllers should validate, delegate, and format responses. Multi-step transactional business logic should move into focused services, following the implemented performance synchronization pattern.

### Database-enforced integrity

Critical identity and ownership rules should be supported by foreign keys and uniqueness constraints rather than relying only on application convention.

### Secure by explicit boundary

Every new route must identify its caller, authentication mechanism, authorization rule, organisation scope, sensitive data handling, and failure behavior.

### Operationally complete changes

A capability is not complete until migrations, rollback behavior, automated tests, API or user documentation, and operational guidance are addressed.

## Decision rights

- Product scope determines which audience and organisation outcomes the platform pursues.
- Architecture decisions define durable system boundaries and are recorded as ADRs.
- Security-sensitive changes require explicit review of authentication, authorization, tenant isolation, and data exposure.
- API contract changes require coordinated provider impact assessment because the current API is unversioned.
- Production operation requires controls beyond the development Compose topology, including backups, monitoring, secrets management, and recovery procedures.

## Measures of platform integrity

The current system does not implement analytics for these measures, but engineering decisions should protect the following outcomes:

- no cross-organisation administrative data access;
- no duplicate show or performance records from repeated provider delivery;
- no successful reuse of an invitation token;
- no pending or rejected review included in public aggregates;
- no provider terminology replacing Encore's core organisation model;
- migrations and tests remaining repeatable across supported development environments.

## Change control

Changes to this charter should be rare and intentional. A change that alters the ownership root, verification model, provider boundary, publication trust model, or administrative authority must include:

1. an architecture decision record;
2. an impact assessment for schema, API, tenancy, and operations;
3. updated domain and security documentation;
4. automated coverage of the new invariants.

Implementation plans and feature roadmaps may evolve without changing this charter when they remain within these boundaries.
