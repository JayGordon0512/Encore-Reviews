# Encore Vision Architecture Review

**Version:** 1.1

**Review date:** 24 July 2026

**Perspective:** Principal Software Architect

**Status:** Advisory

## Scope

This review evaluates the strategic direction expressed in:

- [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md)
- [Platform Strategy](Platform-Strategy.md)
- [Encore Product Blueprint](Encore-Product-Blueprint.md)
- [Audience Journey](Audience-Journey.md)
- [Operating Principles](Operating-Principles.md)
- [Encore Reviews Platform Charter](Encore-Platform-Charter.md)
- [Vision and Product Boundaries](README.md)

It does not rewrite or supersede those documents. It assesses their internal consistency, omissions, product and technical risks, security and AI-governance implications, scalability, and long-term maintainability. The review has been reconciled with the authoritative first versions of the Platform Strategy, Product Blueprint, and Audience Journey.

## Table of Contents

- [Strengths](#strengths)
- [Potential Risks](#potential-risks)
- [Recommendations](#recommendations)
- [Questions for Discussion](#questions-for-discussion)

## Strengths

### Clear strategic differentiation

The Manifesto consistently distinguishes Encore from a transactional ticketing platform and from a conventional review website. Its intended value is the conversion of verified audience experience into useful discovery, insight, and engagement. That provides a strong test for product proposals and helps prevent the platform from becoming an unrelated collection of features.

### Trust is treated as a platform capability

Verified attendance, single-use invitation evidence, moderation before publication, provider-independent identities, and explicit tenant isolation form a coherent initial trust model. The Charter translates the Manifesto's trust principle into concrete system invariants rather than leaving trust as a brand claim.

### Provider independence is explicit

The separation of Encore identities and domain language from provider identifiers is a sound architectural boundary. Idempotent synchronization, UTC normalization, and deliberate provider contracts reduce coupling and create a credible route to supporting multiple providers.

### Current state and future ambition are distinguishable

The Charter and Vision README explicitly list implemented capabilities and exclusions. This limits accidental representation of aspirational features as current functionality and provides a useful governance boundary for engineering work.

### The three platform pillars reinforce one another

Audience Trust, Audience Intelligence, and Audience Engagement describe a plausible platform flywheel. Trustworthy inputs can improve insight; insight can improve discovery and engagement; increased engagement can produce more representative data. The model supports incremental delivery if each stage has explicit evidence and safeguards.

### Privacy and human creativity are first-order principles

The Manifesto states that audience members should control the use of their information, that collection should be transparent and consensual, and that AI should empower rather than replace human creativity. These are valuable constraints for future product and architecture decisions.

### Engineering governance has a credible foundation

The Charter establishes change control, ADR expectations, database-enforced integrity, explicit security boundaries, automated coverage, and operational completeness. These principles will support maintainability as the product and team grow.

## Potential Risks

### Strategic scope is not yet reconciled with the platform mandate

The Manifesto defines Encore as an audience-intelligence, discovery, engagement, marketing, and AI platform. The Charter defines the authoritative current mission as an independent audience review platform. These positions are not inherently contradictory, but the documentation does not define the capability stages, prerequisites, or decision gates that connect them. Teams could either constrain the future vision to today's data model or introduce future capabilities without the required governance.

### The value exchange for audience members is underspecified

Organisations receive insight, targeting, benchmarking, and growth opportunities. Audience members provide verified attendance, reviews, behavioural signals, preferences, and potentially demographic and geographic data. Personal discovery is the stated audience benefit, but the boundaries of collection, profiling, commercialization, consent withdrawal, and non-participation are not defined. A perceived imbalance would undermine the trust on which the flywheel depends.

### Privacy principles conflict with unconstrained data accumulation

"Every audience interaction has value" encourages broad retention and linkage, while privacy law and sound security design require purpose limitation, data minimization, retention limits, and deletion. "People will always control how their information is used" is stronger than the controls currently specified. Without a formal data-governance model, longitudinal profiles and cross-provider identity resolution could exceed user expectations.

### Invisible AI conflicts with transparency and accountability

The intent that AI should be unobtrusive is understandable as product design, but AI that ranks shows, predicts behaviour, segments audiences, summarizes sentiment, or targets marketing should not be invisible in a governance sense. Users and organisations may need disclosure, explanation, correction routes, opt-out controls, provenance, and human review. Treating AI only as an implementation detail creates regulatory, ethical, and reputational risk.

### Intelligent marketing can conflict with audience trust

Personalized recommendations and campaign tools can improve relevance, but the same capabilities can become intrusive profiling, manipulation, exclusion, or excessive contact. Optimizing for ticket sales may conflict with discovery diversity, audience autonomy, accessibility, and equitable exposure for smaller productions. The Manifesto does not establish which principle prevails when these outcomes diverge.

### Moderation authority may weaken review independence

The platform promises trusted reviews while organisation administrators moderate reviews about their own productions. Without platform-wide policy, reasons, appeals, service levels, abuse monitoring, and transparent publication rules, organisations may suppress legitimate criticism. Verified attendance proves eligibility, not truthfulness, fairness, or representativeness.

### Intelligence may be statistically weak or misleading

Invited reviewers are a self-selecting sample. Provider coverage, invitation delivery, response propensity, moderation, digital access, and consent choices can all introduce bias. Demographic insight, benchmarking, sentiment, and prediction may appear authoritative despite small or unrepresentative samples. Poor insight could cause harmful creative or commercial decisions.

### The ownership model may not represent the live-entertainment ecosystem

`Organisation` as a single root owner is effective for initial tenant isolation, but shows and performances can involve co-producers, presenting venues, touring companies, festivals, promoters, and rights holders. A strict ownership tree may struggle with shared administration, cross-organisation analytics, touring productions, venue transfers, and data-controller responsibilities. Because the Charter makes root ownership difficult to change, this needs validation before data volume and integrations make migration expensive.

### Audience identity and consent boundaries are undefined

The vision relies on repeat attendance, personal history, favourites, recommendations, demographic insight, and cross-event learning. These require a durable audience identity or profile, but the current reviewer hash is not a complete identity, account, consent, preference, or rights-management model. Merging identities across ticketing providers could create false matches, duplicates, or unanticipated surveillance.

### Security requirements will expand materially

The future platform would hold behavioural, location, demographic, preference, and inferred data in addition to reviews and attendance evidence. Hashing email addresses without a secret does not prevent dictionary recovery. Provider integrations, customer campaign tools, exports, and AI pipelines increase the attack surface and risks of tenant leakage, re-identification, credential compromise, scraping, model data leakage, and insider misuse. The current application-wide provider secret and application-only tenant scoping will not be sufficient at larger scale or sensitivity.

### The flywheel may amplify bias and concentration

Recommendations trained on existing attendance and engagement can repeatedly favor already popular shows, familiar genres, large organisations, and well-covered regions. This can reduce discovery diversity and create cold-start disadvantages. The assertion that every participant benefits from every interaction is not guaranteed without deliberate marketplace and recommendation objectives.

### Analytics and AI will require a different data architecture

The current transactional monolith is an appropriate early-stage architecture, but event histories, consent state, analytics, segmentation, search, recommendations, and model evaluation have different workload and retention characteristics. Running these directly against the operational database risks performance degradation, accidental exposure, difficult deletion, and tight coupling between product features and mutable schemas.

### Scale dimensions and service objectives are absent

The vision does not state expected organisations, providers, performances, invitations, reviews, events per second, geographic regions, data retention, availability, recovery targets, or freshness expectations. Without these, architectural choices cannot be evaluated objectively and premature complexity or late redesign are both likely.

### Regulatory and safety scope is incomplete

The documents do not address jurisdiction, age assurance and children, accessibility, content rights, special-category data, automated decision-making, data-controller and processor roles, subject-access and deletion requests, international transfers, marketing rules, or safeguarding. Demographic and inferred data make these omissions increasingly significant.

### Strategic-document authority could become ambiguous

The Manifesto is the strategic north star, the Platform Strategy, Product Blueprint, and Audience Journey define product intent, the Charter and ADRs govern approved boundaries, and code and tests are executable truth for current behaviour. Governance must preserve this hierarchy when strategic intent, product policy, legal duty, architecture, and implementation point in different directions.

## Recommendations

### 1. Establish a strategic traceability model

Define a simple hierarchy and change process:

1. Manifesto: enduring purpose and principles.
2. Product Blueprint: target capabilities, value exchanges, business boundaries, success measures, and sequencing.
3. Audience Journey: user states, touchpoints, consent moments, trust expectations, and failure or recovery paths.
4. Charter and policies: binding product, data, security, and operating constraints.
5. Architecture and ADRs: technical realization of approved capabilities.
6. Roadmap and releases: planned and delivered increments.

Each roadmap initiative should identify the vision outcome it advances, the policy constraints it invokes, and the evidence required to proceed.

### 2. Define staged platform evolution

Create explicit capability horizons such as verified reviews, audience accounts and preferences, organisation insight, discovery, engagement, and governed AI. For each horizon, state prerequisites, non-goals, data required, trust risks, operational maturity, and exit criteria. Do not make AI or cross-organisation intelligence a default consequence of collecting reviews.

### 3. Create an audience data and consent charter

Before durable profiles or intelligence features, define:

- data categories and purposes;
- controller and processor responsibilities;
- lawful basis and consent requirements by feature and jurisdiction;
- collection, retention, deletion, portability, correction, and consent-withdrawal rules;
- separation of service messages, recommendations, and direct marketing;
- rules for demographic, location, inferred, and special-category data;
- cross-provider identity matching and account-linking policy;
- aggregation and minimum cohort thresholds;
- permitted and prohibited secondary uses.

Consent should be versioned, attributable, auditable, and enforceable downstream, including analytics exports and model training datasets.

### 4. Replace "invisible AI" with unobtrusive but accountable AI governance

Preserve the intended product principle while establishing governance that includes:

- an inventory of models, datasets, owners, purposes, and risk classifications;
- explicit approval for each AI use case;
- disclosure appropriate to the impact of the feature;
- human oversight, correction, appeal, and opt-out where appropriate;
- offline and production evaluation for accuracy, bias, diversity, safety, and commercial influence;
- provenance and quality controls for generated summaries or recommendations;
- prompt, output, and access controls that prevent tenant or personal-data leakage;
- drift, incident, vendor, model-version, and decommissioning processes;
- a prohibition on using sensitive traits or unsafe proxies for targeting unless specifically justified and approved.

### 5. Define product trust policies separately from implementation

Establish publication and moderation policy, reviewer and organisation standards, conflicts of interest, fraud handling, appeals, takedowns, explainability, and transparency reporting. Consider separating organisation moderation signals from final publication authority or applying independent review to disputed decisions. Measure suppression rates and moderation differences across organisations.

### 6. Validate the domain model against real ecosystem scenarios

Model co-productions, tours, venue hires, festivals, promoters, multiple ticketing providers, merged organisations, rights changes, and shared reporting before cementing the ownership model. Distinguish ownership, tenancy, presentation, production, venue, provider, and data-controller relationships. Preserve strong tenant boundaries even if the model evolves from a tree to explicit relationships.

### 7. Define an audience identity architecture

Separate invitation evidence, reviewer identity, audience account, profile, household, provider identity, consent, and communication preferences. Require explicit linking rather than silently merging provider records. Use keyed pseudonymous identifiers or appropriately encrypted identifiers, formal key rotation, match-confidence rules, merge and split workflows, and a deletion model that propagates to derived data.

### 8. Adopt privacy and security by design before expanding intelligence

Introduce data classification, threat modeling, privacy impact assessment, least-privilege access, per-provider credentials, secret rotation, MFA for privileged users, rate limiting, tamper-resistant audit retention, encryption and key management, export controls, breach response, and tested backup and recovery. Evaluate database row-level security or an equivalently strong tenancy control before exposing flexible analytics and exports.

### 9. Separate operational, analytical, and model workloads deliberately

Retain the monolith while it remains the simplest safe delivery unit, but define seams around provider ingestion, audience identity and consent, review trust, notifications, and intelligence. Use versioned domain events or a controlled change-data mechanism for downstream analytics. Maintain separate governed stores for operational records, analytical aggregates, and model features, with lineage and deletion propagation. Avoid creating distributed services until workload, ownership, or reliability evidence justifies them.

### 10. Establish measurable quality attributes

Set initial and target measures for availability, latency, ingestion throughput, data freshness, recovery time, recovery point, tenant isolation, moderation turnaround, invitation conversion, recommendation quality and diversity, consent withdrawal, deletion completion, model drift, and cost. Include volume assumptions and test them at agreed growth thresholds.

### 11. Guard against feedback loops

Evaluate recommendations across relevance, diversity, novelty, geographic fairness, accessibility, cold start, and exposure for smaller organisations—not only clicks or ticket sales. Clearly distinguish organic recommendations from paid influence. Reserve exploration capacity and monitor whether optimization narrows audience choice or reinforces historic bias.

### 12. Introduce strategic-document governance

Assign an owner, approval authority, version, review cadence, and decision record to each foundational document. Maintain a glossary for terms such as verified, engagement, intelligence, recommendation, audience, organisation, and consent. Add a recorded recommendation register rather than editing vision language when implementation concerns emerge.

## Questions for Discussion

### Product and strategy

1. Is Encore's primary customer the audience, the organisation, or both, and whose interest prevails when outcomes conflict?
2. What is the explicit value exchange for an audience member who permits longitudinal profiling?
3. Which capabilities are core platform commitments, and which are illustrative future possibilities?
4. What is the intended business model, and can organisations pay for reach, ranking, or audience access?
5. How will Encore distinguish recommendation from marketing and disclose commercial influence?
6. What success measures matter beyond ticket sales, and how will audience trust be measured?

### Trust, privacy, and security

7. What exactly is verified in "verified engagement," and what confidence does each verification level provide?
8. Who is the data controller for provider-sourced attendance evidence, Encore profiles, reviews, and derived intelligence?
9. Which data uses require explicit consent, and can a user use core review or discovery features without profiling or marketing?
10. How will withdrawal, deletion, correction, portability, and provider disconnection affect aggregates and trained models?
11. Will children or other vulnerable audiences use the service, and what safeguarding and age-related rules apply?
12. Who has final moderation authority, and how can an audience member challenge suppression or removal?
13. What minimum cohort and anonymization rules apply to demographic, geographic, and benchmarking insight?

### Architecture and scale

14. Can one show, production, performance, or venue be administered by multiple organisations with different rights?
15. Is an Encore audience account required, optional, or intentionally avoided?
16. Will identity be linked across ticketing providers, and if so, through what user-visible authorization and recovery process?
17. What are the three-year volume, geographic, availability, recovery, and data-freshness assumptions?
18. Which insights require real-time processing, and which can be generated asynchronously?
19. What is the migration path when analytical workloads no longer fit the operational database?
20. At what risk or scale threshold should stronger tenant isolation, including database-enforced controls, become mandatory?

### AI governance

21. Which planned decisions are merely assisted by AI, and which could materially affect an audience member or organisation?
22. What explanations and controls should users receive for recommendations, segments, predictions, and generated summaries?
23. May review text, attendance, demographic data, or organisation data be used to train models, including third-party models?
24. Who owns AI outcomes, approves model releases, monitors drift and bias, and can suspend a model?
25. How will Encore detect and limit popularity bias, proxy discrimination, hallucinated insight, and cross-tenant data leakage?

### Governance and maintainability

26. When the Manifesto, Charter, product policy, legal obligations, architecture, and implementation point in different directions, what is the formal decision path?
27. Which principles are non-negotiable, and how are trade-offs recorded when two principles conflict?
28. How often should the strategic foundation be reviewed, and which changes require stakeholder or board approval?
29. Who owns the shared vocabulary and capability map across product, data, AI, legal, security, and engineering?
30. What evidence must a proposed capability provide before it moves from aspiration to roadmap and then implementation?
