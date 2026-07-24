# Encore Roadmap Vision Alignment Review

**Version:** 0.2

**Date:** 24 July 2026

**Perspective:** Product roadmap analysis

**Status:** Advisory — roadmap approval required

## Purpose

This review compares the [current Encore capability roadmap](README.md) with [The Encore Platform Manifesto](../00-Vision/The-Encore-Platform-Manifesto.md), [Platform Strategy](../00-Vision/Platform-Strategy.md), the [Encore Product Blueprint](../00-Vision/Encore-Product-Blueprint.md), and the [Audience Journey](../00-Vision/Audience-Journey.md). It maps every current roadmap capability to the vision objective it supports, the user journey stages it improves, and its contribution to Trust, Intelligence, Discovery, Growth, and Privacy. It then identifies strategic gaps and proposes an updated product roadmap structure.

The mappings use the authoritative Audience Journey and Product Blueprint. Suggested reprioritization still requires the Operating Principles decision framework before it changes the authoritative roadmap.

This document reviews the roadmap; it does not modify or supersede it.

## Table of Contents

- [Journey Model](#journey-model)
- [Contribution Themes](#contribution-themes)
- [Executive Assessment](#executive-assessment)
- [Current Roadmap Alignment](#current-roadmap-alignment)
- [Strategic Roadmap Gaps](#strategic-roadmap-gaps)
- [Suggested Updated Roadmap](#suggested-updated-roadmap)
- [Recommended Delivery Sequence](#recommended-delivery-sequence)
- [Validation Required](#validation-required)

## Journey Model

This review summarizes the authoritative Audience Journey into the following mapping stages.

### Audience journey

1. **Discover** — encounter relevant shows, venues, companies, and trusted review evidence.
2. **Decide** — evaluate fit, confidence, accessibility, and recommendation evidence.
3. **Purchase** — follow through to an approved ticket provider.
4. **Attend** — experience a specific performance.
5. **Verify** — establish trusted evidence of attendance or eligible engagement.
6. **Review** — share an experience with clear expectations and control.
7. **Receive Value** — see history, preferences, recommendations, and the effect of participation.
8. **Return and Engage** — follow favourites, receive chosen updates, and discover another performance.

### Organisation journey

1. **Join and Govern** — establish the organisation, users, roles, permissions, and responsibilities.
2. **Connect and Synchronize** — connect providers and maintain accurate catalogue and performance data.
3. **Collect** — invite eligible audience members and receive verified reviews.
4. **Moderate and Publish** — make accountable content decisions under Encore's trust policy.
5. **Understand** — interpret audience response, behaviour, reach, and change.
6. **Act and Engage** — improve productions, discovery, and relevant audience communication.
7. **Measure and Grow** — evaluate outcomes, build repeat attendance, and reduce marketing waste.

### Provider and Encore operational journey

1. **Onboard** — agree scope, identity, security, data responsibilities, and service expectations.
2. **Operate** — deliver, reconcile, monitor, support, and recover integrations and platform services.
3. **Evolve** — version contracts, extend capability, and scale without weakening trust.

## Contribution Themes

- **Trust:** Verification, review integrity, moderation fairness, reliability, and confidence in platform claims.
- **Intelligence:** Reliable learning, measurement, insight, benchmarking, recommendation, and decision support.
- **Discovery:** Helping audiences find and evaluate relevant live entertainment.
- **Growth:** Repeat engagement, distribution, provider coverage, audience development, conversion, and organisation value.
- **Privacy:** Audience control, purpose limitation, data minimization, tenant isolation, and safe use of personal or inferred data.

## Executive Assessment

The current roadmap is a strong engineering capability portfolio for establishing a secure, provider-neutral, verified-review platform. It provides substantial coverage of:

- production assurance and recoverability;
- organisation tenancy and privileged administration;
- review eligibility and integrity;
- provider ingestion and reconciliation;
- invitation delivery;
- moderation operations;
- public review evidence;
- initial organisation analytics;
- review distribution;
- multi-provider onboarding.

It only partially represents the broader Encore Platform vision. The roadmap currently emphasizes organisation, provider, and operational journeys. It does not yet provide a coherent delivery path for the Manifesto's direct audience relationship, personal discovery, audience-controlled engagement, broad audience intelligence, predictive capabilities, or governed AI.

The current portfolio also treats privacy mainly as security, retention, pseudonymization, and tenant protection. The Manifesto's stronger promise—that audiences control how information is used—requires an explicit audience-facing consent, preference, and data-rights capability.

## Current Roadmap Alignment

### Alignment summary

| Current item | Vision objective supported | Journey stages improved | Trust | Intelligence | Discovery | Growth | Privacy |
| --- | --- | --- | :---: | :---: | :---: | :---: | :---: |
| C01 — Platform assurance and operability | A dependable platform capable of protecting trusted audience experiences | All journeys; Provider/Encore Operate | Primary | Enabler | Enabler | Enabler | Secondary |
| C02 — Organisation identity and tenant governance | Organisations can understand and engage their communities without cross-tenant exposure | Organisation Join and Govern; Provider Onboard | Primary | Enabler | — | Secondary | Primary |
| C03 — Verified review integrity | Verified attendance/review evidence as the foundation of meaningful intelligence | Audience Verify and Review; Organisation Collect | Primary | Enabler | Secondary | Secondary | Primary |
| C04 — Provider ingestion and reconciliation | Accurate performance context and provider-independent platform participation | Audience Attend and Verify; Organisation Connect; Provider Operate | Primary | Enabler | Secondary | Secondary | Secondary |
| C05 — Invitation orchestration and delivery | Help eligible audiences share experiences reliably | Audience Verify and Review; Organisation Collect | Primary | Enabler | — | Secondary | Primary |
| C06 — Moderation governance | Trusted published reviews and accountable content decisions | Audience Review; Organisation Moderate and Publish | Primary | Enabler | Secondary | Secondary | Secondary |
| C07 — Public review intelligence | Help audiences evaluate and discover performances through explainable review evidence | Audience Discover and Decide | Primary | Secondary | Primary | Secondary | Secondary |
| C08 — Organisation analytics and data access | Transform review and operational data into organisation insight | Organisation Understand and Measure | Secondary | Primary | — | Secondary | Primary |
| C09 — Review distribution and widgets | Extend trusted review evidence to organisation and partner surfaces | Audience Discover and Decide; Organisation Act and Grow | Primary | Secondary | Primary | Primary | Secondary |
| C10 — Multi-provider integration ecosystem | Expand trusted participation without provider dependence | Audience Attend and Verify; Organisation Connect; Provider Onboard/Operate/Evolve | Primary | Enabler | Secondary | Primary | Primary |

“Enabler” means the capability is necessary for the theme but does not itself deliver the user-facing outcome. A dash means no meaningful direct contribution is currently described.

### C01 — Platform Assurance and Operability

**Vision objective supported:** Protect the trust on which all audience experience and intelligence depend; ensure every interaction can strengthen rather than destabilize the platform.

**Journey stages improved:** All audience and organisation stages indirectly; Provider/Encore Operate directly.

**Theme contribution:** Trust is primary. Intelligence, Discovery, and Growth are enabled through availability and recoverability. Privacy is supported through secrets, access, retention, incident, and operational controls.

**Assessment:** Strongly aligned and correctly foundational. The current item should expand its product outcome measures beyond engineering health to include audience-facing reliability, invitation completion, publication freshness, and recovery expectations.

### C02 — Organisation Identity and Tenant Governance

**Vision objective supported:** Enable organisations to understand and engage their own communities while protecting trust and data boundaries.

**Journey stages improved:** Organisation Join and Govern; Provider Onboard where integration ownership is established.

**Theme contribution:** Trust and Privacy are primary. Growth is secondary through safe customer onboarding. Intelligence is enabled by correct organisation scope.

**Assessment:** Strong coverage of organisation identity, but the vision requires an equivalent audience identity and control capability. Organisation roles should eventually distinguish moderation, analytics, campaign, and administrative responsibilities.

### C03 — Verified Review Integrity

**Vision objective supported:** Verified audience experience as the source of trusted insight; reviews as the mechanism through which audience intelligence is created.

**Journey stages improved:** Audience Verify and Review; Organisation Collect.

**Theme contribution:** Trust is primary. Intelligence, Discovery, and Growth benefit from higher-quality source evidence. Privacy is material because reviewer identity and evidence must be protected.

**Assessment:** Directly aligned and appropriately prioritized. The capability should define verification levels and connect review provenance to the exact eligibility evidence. It does not yet cover the broader Manifesto phrase “verified engagement.”

### C04 — Provider Ingestion and Reconciliation

**Vision objective supported:** Maintain accurate performance context while ensuring Encore remains independent from a single ticketing provider.

**Journey stages improved:** Audience Attend and Verify; Organisation Connect and Synchronize; Provider Operate.

**Theme contribution:** Trust is primary. Intelligence and Discovery depend on accurate catalogue and attendance context. Growth benefits from reliable partner participation. Privacy is supported through scoped provider access.

**Assessment:** Strong strategic enabler. It should be explicitly linked to evidence quality and catalogue freshness outcomes, not only synchronization correctness.

### C05 — Invitation Orchestration and Delivery

**Vision objective supported:** Make it straightforward for verified attendees to share their experience and contribute to the flywheel.

**Journey stages improved:** Audience Verify and Review; Organisation Collect.

**Theme contribution:** Trust and Privacy are primary. Intelligence and Growth benefit from increased valid response volume.

**Assessment:** Well aligned, but the roadmap should include audience experience measures such as delivery reach, completion, expiry, accessibility, frequency, and perceived legitimacy—not only operational delivery state.

### C06 — Moderation Governance

**Vision objective supported:** Preserve trusted reviews and accountable publication.

**Journey stages improved:** Audience Review; Organisation Moderate and Publish.

**Theme contribution:** Trust is primary. Intelligence and Discovery depend on accurate publication state. Growth benefits from credible content. Privacy is relevant to moderation notes and reviewer information.

**Assessment:** Necessary, but current decision authority remains organisation-centered. The roadmap does not yet commit to an independent publication policy, audience appeal, disputed-decision path, or transparency reporting. Those are required to substantiate platform independence.

### C07 — Public Review Intelligence

**Vision objective supported:** Help audiences discover relevant performances and understand trusted audience experience.

**Journey stages improved:** Audience Discover and Decide.

**Theme contribution:** Discovery and Trust are primary. Intelligence is delivered through scores and explainable aggregates. Growth benefits from qualified ticket interest. Privacy limits public fields and projections.

**Assessment:** The title suggests broader intelligence than the scope currently contains. The capability covers public review evidence and potential search, but not personal recommendations, similar shows, venue discovery, local discovery, favourites, or watchlists.

### C08 — Organisation Analytics and Data Access

**Vision objective supported:** Help organisations understand audience response and turn trusted information into decisions.

**Journey stages improved:** Organisation Understand and Measure and Grow.

**Theme contribution:** Intelligence is primary. Privacy is primary where cohorts and exports could expose audience information. Growth is secondary through better decisions. Trust depends on reproducible definitions.

**Assessment:** Strategically important but narrower than the Manifesto. It currently centers on review and operational analytics. The vision also calls for sentiment, geographic reach, attendance behaviour, repeat attendance, recommendation rates, production benchmarking, marketing effectiveness, audience growth, and predictive insight.

### C09 — Review Distribution and Widgets

**Vision objective supported:** Extend trusted audience evidence to more discovery surfaces and help organisations make that evidence useful.

**Journey stages improved:** Audience Discover and Decide; Organisation Act and Grow.

**Theme contribution:** Growth and Discovery are primary. Trust protects attribution and moderation state. Intelligence is distributed rather than created. Privacy limits usage analytics and public fields.

**Assessment:** Valuable as a distribution capability, but it should follow evidence that widgets meaningfully improve audience decisions or organisation acquisition. It is less central to the Manifesto than the missing direct audience experience and organisation intelligence capabilities.

### C10 — Multi-Provider Integration Ecosystem

**Vision objective supported:** Become a platform for live entertainment rather than a feature dependent on one provider; increase the coverage that powers trusted insight.

**Journey stages improved:** Audience Attend and Verify; Organisation Connect and Synchronize; Provider Onboard, Operate, and Evolve.

**Theme contribution:** Growth and Trust are primary. Privacy is material to provider isolation and data responsibility. Intelligence and Discovery benefit from broader coverage.

**Assessment:** Strongly aligned. Delivery should be validated with a second provider before a generalized ecosystem proposition is committed. The product roadmap should describe partner onboarding, value exchange, and support outcomes alongside technical conformance.

## Strategic Roadmap Gaps

### Gap summary

| Gap | Unsupported or under-supported strategic objective | Missing journey coverage | Priority |
| --- | --- | --- | --- |
| Audience identity, consent, preferences, and data rights | People control how their information is used; personal history and recommendations | Audience Receive Value and Return; trust moments across all stages | Critical |
| Independent review policy, appeals, and transparency | Trustworthy audience experiences and platform independence | Audience Review and Receive Value | Critical |
| Verified attendance and engagement definition | Verified attendance, reviews, and engagement | Audience Attend, Verify, and Review | Critical |
| Audience signal and intelligence measurement foundation | Every interaction becomes responsible knowledge | Audience Receive Value; Organisation Understand | Critical |
| Search and local discovery | Show discovery, venue discovery, What's On Near You | Audience Discover and Decide | High |
| Audience library | Favourites, watchlists, follows, personal review history | Audience Receive Value and Return | High |
| Similar and personalized recommendations | Personal discovery and trusted companion proposition | Audience Discover, Decide, and Return | High |
| Follow-based alerts and weekly discovery | Favourite venue/company updates and regular discovery | Audience Return and Engage | High |
| Full organisation intelligence | Sentiment, reach, attendance, repeat behaviour, growth, effectiveness | Organisation Understand and Measure | High |
| Action-oriented decision guidance | Every insight answers “What should I do next?” | Organisation Act and Engage | High |
| Benchmarking | Production comparison and performance context | Organisation Understand and Measure | Medium |
| Consented geographic and demographic insight | Understand audience reach where explicitly permitted | Organisation Understand; Audience trust and control | Medium |
| Marketing effectiveness | Better targeting, less waste, stronger communities | Organisation Measure and Grow | Medium |
| Intelligent campaigns | Reach audiences most likely to attend without intrusive messaging | Organisation Act and Engage; Audience Return | Medium |
| Recommendation and campaign diversity safeguards | Avoid prioritizing only the largest marketing budgets | Audience Discover and Decide | High |
| AI governance and user transparency | AI as a responsible intelligence layer | Audience Receive Value; Organisation Understand/Act | Critical before AI |
| AI-supported insight | Transform large audience experience volumes into understanding | Organisation Understand and Act | Future |
| Predictive insight | Identify trends and changing behaviour earlier | Organisation Understand and Act | Future |
| AI-powered engagement | Future intelligent audience engagement | Audience Return; Organisation Act and Grow | Future |

### 1. The audience relationship is not a roadmap capability

The roadmap contains organisation identity but no audience account, profile, consent, preference, history, data-rights, or identity-linking capability. This leaves the Audience Trust and Audience Engagement pillars without a product foundation and prevents responsible personalization.

### 2. Discovery is narrower than the vision

C07 covers public reviews and possible search projections, while the Manifesto explicitly describes show discovery, venue discovery, local discovery, similar shows, personal recommendations, favourites, companies, watchlists, and weekly updates. These need a coherent audience experience portfolio rather than being assumed consequences of public review pages.

### 3. Organisation intelligence is too narrowly defined

C08 is an appropriate starting point, but its sources and outcomes remain largely review-operational. It does not provide a staged plan for the complete intelligence promise or explain how organisations move from a metric to an action and then measure the result.

### 4. Privacy is cross-cutting but not audience-facing

Current capabilities mention retention, pseudonymization, cohort suppression, exports, and tenant isolation. There is no roadmap item that delivers the Manifesto's promise of audience control across collection, profile, recommendation, analytics, marketing, withdrawal, and deletion.

### 5. AI has no delivery or governance path

The Manifesto places AI throughout the future platform, but the roadmap contains neither candidate product use cases nor a capability for evaluation, transparency, human oversight, incident handling, or model lifecycle. AI should not be inserted into C07 or C08 without an explicit product and trust gate.

### 6. Growth is represented primarily through distribution and provider reach

C09 and C10 improve platform reach, but the audience flywheel also depends on retention, follow-based engagement, recommendation outcomes, organisation action, campaign measurement, and repeat attendance. These loops have no planned product ownership.

### 7. Strategic outcomes lack portfolio measures

Current acceptance criteria are predominantly functional, security, operational, and contractual. They do not yet establish portfolio measures for audience trust, discovery success, review representativeness, repeat use, recommendation diversity, organisation decision value, or audience growth.

## Suggested Updated Roadmap

The proposed structure retains the valuable current capabilities while making missing strategic outcomes visible. “Continue” retains a current capability; “Expand” broadens a current capability; “Add” introduces a missing product capability. This is a suggested product portfolio, not a delivery commitment.

## Foundation

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| F01 — Platform assurance and service trust | Continue C01 | Encore is secure, observable, recoverable, and dependable at every journey stage | Trust, Privacy, Scale | Service objectives, production operations, security ownership |
| F02 — Organisation identity, roles, and tenant governance | Expand C02 | Organisation users have purpose-specific access without cross-tenant exposure | Trust, Privacy | Role model, organisation lifecycle, support policy |
| F03 — Audience identity and trust centre | Add | Audiences can manage identity, consent, preferences, communication, history, and data rights | Trust, Privacy | Product Blueprint, Audience Journey, privacy policy, account proposition |
| F04 — Verified evidence and review integrity | Expand C03 | Verification claims have consistent evidence levels and complete provenance | Trust, Privacy, Intelligence | Attendance policy, provider evidence, fraud/dispute rules |
| F05 — Independent moderation and publication governance | Expand C06 | Review decisions are fair, accountable, appealable, and transparent | Trust | Publication policy, operating model, legal/content policy |
| F06 — Provider ingestion and reconciliation | Continue C04 | Accurate catalogue and evidence converge reliably | Trust, Scale | Provider contracts, event/queue decisions, monitoring |
| F07 — Invitation and eligibility lifecycle | Expand C05 | Eligible audiences receive a clear, reliable, respectful review journey | Trust, Growth, Privacy | Evidence model, communication rules, accessible journey design |
| F08 — Permissioned signal and measurement foundation | Add | Product interactions become governed, defined, and quality-assessed inputs to learning | Intelligence, Privacy, Trust | Consent, metric catalogue, telemetry policy, quality standards |

## Core Experience

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| E01 — Trusted review submission and status journey | Expand C03/C05/C06 | Audiences can submit, understand, and resolve the status of their contribution | Trust, Growth | F03–F07, audience research, accessible content |
| E02 — Public review evidence | Refocus C07 | Audiences can evaluate shows through explainable approved evidence | Trust, Discovery | F04/F05, public metric definitions |
| E03 — Catalogue search and filters | Add within expanded C07 | Audiences can find relevant live entertainment efficiently | Discovery, Growth | Accurate catalogue, search needs, accessibility |
| E04 — Local and venue discovery | Add | Audiences can discover performances, venues, and companies in relevant places | Discovery, Growth, Privacy | Location policy, venue/company identity, catalogue quality |
| E05 — Audience library | Add | Audiences have review history, favourites, follows, and watchlists | Discovery, Growth, Privacy | F03, stable public identities, archive behavior |
| E06 — Similar-show recommendations | Add | Audiences can continue discovery from a known show using transparent relevance | Discovery, Intelligence, Growth | E02/E03, content quality, diversity objective |
| E07 — Review distribution and widgets | Retain C09 after validation | Trusted public evidence reaches approved partner surfaces | Discovery, Growth, Trust | Stable public contracts, measured partner need, moderation freshness |

## Intelligence

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| I01 — Metric catalogue and data-quality experience | Expand C08 | Every insight has one definition, provenance, quality, and interpretation | Intelligence, Trust, Privacy | F08, metric ownership, disclosure standards |
| I02 — Organisation review and response insight | Continue initial C08 | Organisations understand trusted review patterns and response | Intelligence, Trust | F04/F05, I01 |
| I03 — Audience behaviour and repeat engagement | Add | Organisations understand attendance and return patterns where permitted | Intelligence, Growth, Privacy | F03/F08, provider coverage, longitudinal policy |
| I04 — Geographic reach and consented demographics | Add | Organisations can evaluate reach and inclusion without exposing individuals | Intelligence, Privacy | Consent, cohort thresholds, representative coverage |
| I05 — Production benchmarking | Add | Organisations can compare performance with valid history and peer context | Intelligence, Growth, Privacy | I01–I04, sufficient comparable coverage |
| I06 — Marketing effectiveness | Add | Organisations understand which activity contributes to discovery and attendance | Intelligence, Growth, Privacy | Attribution policy, provider outcomes, campaign definitions |
| I07 — Action-oriented insight | Add | Users can move from evidence to an appropriate next action | Intelligence, Growth, Trust | User decision research, I01–I06, outcome feedback |

## AI

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| A01 — AI governance and transparency | Add | Every AI use has purpose, owner, evaluation, disclosure, oversight, and suspension | Trust, Privacy, Intelligence | Approved AI policy, data rights, decision ownership |
| A02 — Review themes and evidence-linked summaries | Add | Organisations understand large review volumes efficiently without losing source context | Intelligence, Trust | A01, I01/I02, sufficient reviewed content |
| A03 — Personalized recommendations | Add after E06 | Consenting audiences receive relevant, diverse, explainable discovery | Discovery, Intelligence, Growth, Privacy | F03/F08, E05/E06, A01, diversity policy |
| A04 — Predictive audience insight | Add later | Organisations can anticipate defined changes with visible uncertainty | Intelligence, Growth, Trust | Longitudinal quality, I03–I07, A01, outcome history |
| A05 — AI-assisted engagement | Add later | Organisations receive governed suggestions without autonomous uncontrolled targeting | Growth, Intelligence, Privacy, Trust | A01, G02/G03, effectiveness evidence, human approval |

## Growth

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| G01 — Follow-based alerts and weekly discovery | Add | Audiences return for updates they explicitly chose | Growth, Discovery, Privacy | F03, E04/E05, communication policy |
| G02 — Audience engagement governance | Add | Consent, frequency, suppression, and channel choices protect every communication | Trust, Privacy, Growth | F03, audience value exchange, marketing rules |
| G03 — Intelligent campaign tools | Add | Organisations reach eligible interested audiences through governed segments | Growth, Intelligence, Privacy | G02, I03/I06, organisation entitlements |
| G04 — Review distribution partnerships | Continue C09 | Approved evidence supports audience decisions beyond Encore-owned surfaces | Growth, Discovery, Trust | E02/E07, partner proposition, outcome measures |
| G05 — Multi-provider ecosystem | Continue C10 | More organisations and audiences participate without provider lock-in | Growth, Trust, Privacy | Mature F06/F07, second-provider validation, commercial model |
| G06 — Flywheel outcome measurement | Add | Encore can show how trust, discovery, engagement, and attendance reinforce one another | Growth, Intelligence, Trust | F08, E/I/G capabilities, agreed outcome model |

## Scale

| Proposed item | Relationship to current roadmap | Product outcome | Primary themes | Key dependencies |
| --- | --- | --- | --- | --- |
| S01 — Asynchronous work and recovery | Expand C01/C04/C05 | Delivery, reconciliation, indexing, exports, and communication remain recoverable under load | Trust, Scale | Accepted processing decisions, operational ownership |
| S02 — Public discovery performance | Expand C07 | Search, scores, and public content remain responsive and fresh as catalogue use grows | Discovery, Growth, Trust | E02–E06, measured demand, freshness objectives |
| S03 — Governed analytical scale | Expand C08 | Intelligence workloads grow without weakening transactional experience or privacy | Intelligence, Privacy, Trust | I01, measured workload, retention and lineage |
| S04 — Provider workload isolation | Expand C10 | One provider cannot degrade or expose another provider's operation | Trust, Growth, Privacy | G05, provider service objectives, capacity evidence |
| S05 — Data lifecycle at scale | Add | Retention, correction, withdrawal, deletion, and model/data propagation remain reliable | Privacy, Trust | F03/F08, I/A capabilities, rights policy |
| S06 — Recommendation and AI operating scale | Add when justified | Models remain available, measurable, reversible, and cost-controlled | Intelligence, Discovery, Trust | A01–A05, proven product value, service objectives |

## Recommended Delivery Sequence

The roadmap groups above describe strategic portfolios, not a mandate to run six independent workstreams at once. The recommended sequence is:

1. **Complete the trust foundation:** F01–F08, including the missing audience identity, evidence, moderation, consent, and measurement decisions.
2. **Deliver the direct audience value loop:** E01–E06, beginning with review status, public evidence, search, local discovery, and explicit audience preferences.
3. **Deliver explainable organisation intelligence:** I01–I04 before benchmarking, action guidance, or prediction.
4. **Establish audience-controlled growth:** G01/G02 and outcome measurement before intelligent campaigns.
5. **Introduce governed AI incrementally:** A01 first; A02 and A03 only after their data and experience foundations; A04/A05 only after sustained outcome evidence.
6. **Scale measured constraints:** advance S01–S06 when usage, sensitivity, reliability, or workload evidence crosses an agreed threshold.
7. **Expand ecosystem reach:** continue G04/G05 when the core audience and organisation propositions are demonstrably valuable and supportable.

### Suggested reprioritization of current capabilities

- Retain C01–C06 as Foundation work, with C03/C05/C06 expanded to cover the complete audience trust journey.
- Split C07 into Public Review Evidence, Search, Local/Venue Discovery, Audience Library, and Similar-Show Recommendations so direct audience value is visible.
- Expand C08 into a staged Intelligence portfolio rather than a single analytics/export capability.
- Keep C09 behind validation of partner demand and direct audience priorities; it should not displace missing audience identity or discovery foundations.
- Continue C10 after proving the provider operating model with a second integration and agreeing the partner value proposition.
- Add explicit AI Governance before any AI delivery item.
- Add Audience Identity and Trust Centre as a P0 capability because personalization, longitudinal intelligence, and engagement depend on it.

## Validation Required

Before adopting the suggested roadmap:

1. Re-run the mapping against the approved Product Blueprint and replace inferred objectives with its exact capability and outcome definitions.
2. Confirm that roadmap capability acceptance criteria cover the approved Audience Journey's failure, recovery, consent, and non-participation paths.
3. Decide whether the roadmap is one portfolio for audiences, organisations, providers, and platform operations or whether each requires a linked roadmap view.
4. Assign product owners and measurable outcomes to Trust, Intelligence, Discovery, Growth, and Privacy.
5. Confirm which Manifesto examples are commitments, experiments, or deliberately distant possibilities.
6. Establish sequencing and investment constraints; priority alone should not imply concurrent delivery.
7. Record strategic trade-offs where audience control, organisation value, commercial growth, recommendation diversity, and operational cost conflict.
