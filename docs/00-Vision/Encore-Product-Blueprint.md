# Encore Product Blueprint

**Version:** 1.0

**Status:** Authoritative product vision

**Owner:** Chief Product & Strategy Architect

## Purpose

This blueprint defines the intended product shape of the Encore Platform. It translates [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md) into product principles, audiences, capabilities, boundaries, and outcomes.

It describes the product Encore is becoming. It does not claim that every capability is currently implemented. Current implementation remains documented separately in the engineering handbook, roadmap, and release records.

## Table of Contents

- [Product Definition](#product-definition)
- [Product Promise](#product-promise)
- [Primary Participants](#primary-participants)
- [Product Principles](#product-principles)
- [The Platform Model](#the-platform-model)
- [Audience Experience](#audience-experience)
- [Organisation Experience](#organisation-experience)
- [Trust and Contribution](#trust-and-contribution)
- [Platform Ecosystem](#platform-ecosystem)
- [Audience Intelligence](#audience-intelligence)
- [Audience Engagement](#audience-engagement)
- [The Role of AI](#the-role-of-ai)
- [Privacy and Data Use](#privacy-and-data-use)
- [Commercial Product Model](#commercial-product-model)
- [Product Outcomes](#product-outcomes)
- [Product Boundaries](#product-boundaries)
- [Delivery Principles](#delivery-principles)
- [Assumptions Requiring Validation](#assumptions-requiring-validation)
- [Related Strategic Foundation](#related-strategic-foundation)

## Product Definition

Encore is the Audience Intelligence Platform for Live Entertainment.

It helps audiences discover performances with confidence and helps live-entertainment organisations understand, grow, and engage their communities through trusted audience experience.

Encore is not a ticketing platform and is not simply a review website. Ticketing providers establish transaction and attendance context. Encore transforms verified audience experience into trusted discovery, useful intelligence, and responsible engagement.

## Product Promise

> **At Encore, anyone can discover. Only people who were there can contribute.**

Encore provides open access to discovery and public review evidence while protecting trusted contribution through explicit verification.

The platform should help every participant answer a valuable question:

- **Audience:** What should I discover next?
- **Organisation:** What did our audience experience, and what should we do next?
- **Venue or producer:** How can we create and connect people with stronger live experiences?
- **Provider:** How can trusted post-purchase audience value strengthen our customer proposition?

## Primary Participants

### Audiences

People discovering, booking, attending, reviewing, following, and returning to live entertainment.

### Organisations

Theatre companies, venues, festivals, schools, colleges, touring companies, promoters, producers, and other live-event operators seeking trusted audience understanding.

### Providers

Ticketing and attendance systems that supply approved event, performance, booking, ticket, or attendance context through explicit integration agreements.

### Encore Operations

The people responsible for platform trust, organisation support, integration governance, publication policy, and product integrity.

## Product Principles

### Trust Before Scale

Audience Intelligence is valuable only when its source and limitations are understood. Verification, publication governance, privacy, and data quality must grow before the breadth of insight or engagement grows.

### Authority Must Be Earned

Membership and authority are separate concepts.

An Encore account establishes identity and unlocks membership features. It does not automatically authorize contribution.

Review permissions originate from verified attendance through a Verified Review Invitation. Every trusted review must retain clear provenance to that authority.

Future audience contribution features should follow the same philosophy. Before implementation, each feature must define what authority is required, how it is verified, how it expires or is revoked, and how its provenance is preserved.

### Open Discovery

Anyone can browse shows, venues, organisations, and approved reviews without creating an account. Account creation must not become an artificial barrier to public discovery.

### Audience Value Is Explicit

When Encore asks an audience member to contribute information, the purpose and benefit should be understandable. Participation should create value through trust, history, control, discovery, or more relevant experiences.

### Insight Must Lead to Action

Dashboards and data are delivery mechanisms, not outcomes. Organisation insight should help a named user make a clearer decision and understand the evidence behind it.

### Personalisation Is Controlled

Personalisation should use explicit preferences and permissioned activity. Audiences should be able to inspect, influence, reset, or disable personalisation without losing access to core public discovery.

### Marketing Must Respect the Audience

Audience engagement should improve relevance, not maximize contact. Consent, frequency, suppression, audience autonomy, and discovery diversity are product requirements.

### AI Must Create Measurable Value

AI is an enabling capability, not the product. It should be used only when it measurably improves Discovery, Insight, Decision-making, or Audience experience beyond a simpler deterministic approach.

### Provider Independence

Encore owns its domain language, audience relationship, trust rules, and product roadmap. Providers integrate with Encore but do not define the platform's core identity or authority model.

## The Platform Model

Encore is built on three connected product pillars.

### Audience Trust

- Open public discovery
- Verified attendance evidence
- Verified Review Invitations
- Verified reviews
- Independent publication standards
- Transparent scores and recommendation rates
- Audience identity and data control
- Clear contribution status and recourse

### Audience Intelligence

- Audience sentiment and recurring themes
- Recommendation rates
- Attendance and response patterns
- Repeat engagement
- Geographic reach where permitted
- Consented demographic insight
- Production and historical benchmarking
- Marketing effectiveness
- Audience growth
- Action-oriented and, when justified, predictive insight

### Audience Engagement

- Favourites and follows
- Watchlists
- Personal review and entertainment history
- Local and venue discovery
- Similar-show recommendations
- Optional personal recommendations
- Requested alerts and weekly discovery
- Governed organisation engagement
- Future AI assistance only where measurable value is proven

## Audience Experience

### Public Experience

Anyone can:

- discover shows, venues, and organisations;
- browse approved reviews and explainable aggregates;
- follow ticket links to supported providers;
- understand why a review is verified;
- use non-personalised discovery without an Encore account.

### Contribution Experience

An eligible attendee can:

- receive a Verified Review Invitation after attendance;
- understand which performance the invitation covers;
- submit one review under the invitation's authority;
- understand moderation and publication status;
- create an Encore account optionally after contribution.

### Membership Experience

An Encore account may unlock:

- saved favourites;
- followed venues and organisations;
- personal recommendations;
- entertainment and review history;
- watchlists;
- notification preferences;
- privacy, consent, and data controls.

Membership never creates review authority by itself.

The authoritative audience flow is maintained in the [Audience Journey](Audience-Journey.md).

## Organisation Experience

### Join and Connect

Organisations establish identity, users, roles, catalogue ownership or participation, and approved provider connections.

### Collect Trusted Experience

Organisations use approved attendance evidence and invitation journeys to collect verified audience feedback without deciding who is considered to have attended outside the agreed verification rules.

### Govern and Respond

Organisation users work within transparent moderation and publication policy. Review decisions must be attributable and subject to the authority, escalation, and appeal model defined by Encore.

### Understand

Organisations receive clearly defined metrics, trends, evidence, sample context, and known limitations. Insight should distinguish observation, interpretation, and recommendation.

### Act and Measure

Encore should help organisations improve productions, audience experience, discovery, and responsible engagement, then understand whether the action produced value.

## Trust and Contribution

### Identity Grants Access

Identity answers who the participant is and which personal or organisational capabilities they may access.

### Verification Grants Authority

Verification answers what trusted contribution the participant is authorized to make and why.

For reviews, authority derives from verified attendance and is represented by a Verified Review Invitation linked to a specific performance.

### Authority Has Provenance

Every trusted contribution must retain:

- the type of authority granted;
- the evidence or approved source supporting it;
- the subject and scope of the authority;
- issue, expiry, consumption, revocation, or correction state;
- the contribution created under that authority.

### Verification Does Not Guarantee Opinion

Verification establishes attendance and contribution eligibility. It does not make a subjective opinion objectively correct. Publication policy, abuse controls, and recourse remain separate trust mechanisms.

## Platform Ecosystem

### Provider Abstraction

Encore uses provider-agnostic product and domain concepts for organisations, shows, performances, venues, attendance evidence, review authority, and reviews.

Provider identifiers, credentials, terminology, payloads, and operational behavior remain at explicit integration boundaries.

### Native Integrations

A native integration is an approved, supported connection that can deliver a defined set of Encore capabilities with clear security, reliability, reconciliation, and support responsibilities.

TicketPal is the flagship native integration and should provide the richest, deepest, and most seamless Encore experience.

### Third-Party Providers

Encore is designed to support approved third-party ticketing and attendance providers. Organisations should not be required to replace their ticketing platform in order to adopt Encore.

Provider participation requires a governed contract, evidence model, credential scope, data-purpose agreement, conformance testing, and operational ownership.

### Graceful Feature Degradation

Encore capabilities may vary by provider according to the data and workflows available. Missing provider capability must degrade explicitly and safely rather than producing false equivalence.

For example:

- a provider may support catalogue synchronization but not attendance evidence;
- verified review invitations may require an approved alternative evidence path;
- real-time updates may fall back to scheduled reconciliation;
- advanced organisation insight may remain unavailable when source coverage is insufficient.

The product must communicate capability and evidence differences clearly. A weaker integration must never be presented as equally verified merely for consistency of appearance.

### Future Extensibility

New providers and contribution types should be introduced through stable Encore concepts and explicit capabilities. Extensibility must not weaken verification, privacy, tenant isolation, product coherence, or the independent value of Encore and each provider.

## Audience Intelligence

Audience Intelligence is derived from trusted, permissioned, quality-assessed inputs.

Every organisation-facing insight should identify:

- what is being measured;
- the source and period;
- inclusion and exclusion rules;
- sample and coverage limitations;
- whether it is observed, inferred, or predicted;
- the action or decision it is intended to support.

Encore should deliver descriptive understanding before prediction. Organisation-owned historical comparison should precede broad peer benchmarking. AI-supported interpretation should follow trusted deterministic metrics.

## Audience Engagement

The preferred engagement sequence is:

1. audience-declared favourites, follows, and watchlists;
2. audience-requested alerts and discovery updates;
3. transparent similar-show recommendations;
4. optional permissioned personal recommendations;
5. governed organisation engagement with consent, suppression, and frequency controls;
6. AI assistance only after lower-risk approaches demonstrate value.

Organisations should not receive unrestricted audience identities or opaque targeting power merely because they use Encore.

## The Role of AI

AI may support:

- review theme classification;
- evidence-linked summarisation;
- recommendation ranking;
- trend detection;
- carefully bounded prediction;
- assistive organisation decision support.

AI must not:

- manufacture evidence or audience consensus;
- override consent, suppression, verification, or publication policy;
- silently infer sensitive audience characteristics;
- autonomously contact audiences under the current strategic model;
- replace accountable creative or commercial judgment;
- be implemented without a deterministic baseline and measurable value test.

AI insight inherits its trust from verified audience data, governed metrics, visible provenance, and appropriate human oversight.

## Privacy and Data Use

Encore should collect the minimum information needed for an approved purpose and retain it only for an understood period.

Audience members should be able to understand and control:

- account and profile information;
- personalisation inputs;
- favourites, follows, watchlists, and history;
- communication preferences;
- consented demographic or geographic information;
- permitted analytics and AI uses;
- correction, export, withdrawal, and deletion where applicable.

Public participation, account membership, recommendation consent, direct marketing consent, and contribution authority are separate decisions.

## Commercial Product Model

Encore should compete through product quality, intelligence, trust, and integration value rather than lock-in.

Potential product value may be packaged through:

- organisation subscriptions for trusted review operations;
- intelligence and benchmarking capabilities;
- enterprise governance and portfolio reporting;
- provider and strategic partnerships;
- approved review distribution;
- governed audience engagement;
- future APIs and intelligence products with explicit data rights.

Encore must not monetize through sale of identifiable audience data, hidden paid ranking, payment-dependent publication, or engagement models that reward intrusive contact.

The ecosystem and commercial strategy is defined further in [Platform Strategy](Platform-Strategy.md).

## Product Outcomes

Encore should measure progress through outcomes such as:

### Trust

- proportion of contributions with understood verification provenance;
- audience understanding of verification and contribution status;
- moderation consistency, appeal, and correction outcomes;
- consent, complaint, and trust measures.

### Discovery

- successful show and venue discovery;
- saves, follows, watchlists, and useful recommendations;
- diversity and novelty of exposure;
- qualified outbound ticket interest.

### Intelligence

- organisation users reaching a useful insight;
- decisions changed or clarified;
- metric quality, coverage, and correction;
- repeated use of insight capabilities.

### Growth

- repeat audience engagement;
- organisation activation, retention, and expansion;
- provider coverage and integration health;
- measurable reduction in wasted communication or improved audience reach.

## Product Boundaries

Encore is not:

- a ticketing system;
- an open anonymous review platform;
- an audience-data marketplace;
- a pay-to-rank discovery product;
- an unrestricted customer messaging database;
- an AI product searching for a use case;
- a replacement for human creativity or accountable decision-making.

## Delivery Principles

Every significant product initiative must:

1. identify the vision objective it advances;
2. identify the audience or organisation journey stage it improves;
3. define the problem and measurable outcome;
4. distinguish identity, access, authority, and consent;
5. define trust, privacy, and failure behavior;
6. choose the simplest approach that can prove value;
7. complete Strategic Review, Engineering Review, and Founder Approval;
8. update product, architecture, operations, and release documentation as applicable.

Before implementation ask:

> Does this move Encore closer to becoming the Audience Intelligence Platform for Live Entertainment?

If the answer is unclear, the initiative should return to product review.

## Assumptions Requiring Validation

The following strategic assumptions require evidence before becoming delivery commitments:

- audiences will create optional Encore accounts after review contribution;
- personal history, favourites, follows, watchlists, and discovery create repeat audience value;
- organisations will pay for trusted intelligence beyond review collection;
- multiple providers can supply comparable verification evidence;
- cross-organisation benchmarks can be representative and privacy-safe;
- permissioned engagement can create value without weakening audience trust;
- AI can improve selected tasks beyond deterministic software at acceptable cost and risk.

## Related Strategic Foundation

- [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md)
- [Audience Journey](Audience-Journey.md)
- [Platform Strategy](Platform-Strategy.md)
- [Operating Principles](Operating-Principles.md)
- [Encore Reviews Platform Charter](Encore-Platform-Charter.md)
- [ADR-015: Authority Through Verification](../02-ADR/ADR-015-authority-through-verification.md)
