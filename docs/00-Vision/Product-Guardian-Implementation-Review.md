# Product Guardian Review of the Proposed Encore Implementation

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 0.1

**Date:** 24 July 2026

**Reviewer role:** Product Guardian

**Status:** Superseded by the [Authority Principle Product Guardian Review](Authority-Principle-Product-Guardian-Review.md)

## Purpose

This review evaluates the proposed [Encore Engineering Implementation Plan](Encore-Engineering-Implementation-Plan.md) against the platform vision and determines whether it protects Encore's long-term product integrity.

The requested vision sources are:

- [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md)
- `Encore-Product-Blueprint.md`
- `Audience-Journey.md`

At the time of this review, the Product Blueprint and Audience Journey were not present in the workspace. That historical limitation is now resolved. Current strategic consistency is assessed in the [Authority Principle Product Guardian Review](Authority-Principle-Product-Guardian-Review.md); this document is retained as the original implementation-plan review and must not be treated as the current approval record.

The implementation plan correctly describes itself as proposed and subject to blueprint validation. This review reinforces that constraint: it must not be treated as an approved delivery baseline until the missing product sources are available and the required governance stages are complete.

## Rating Scale

- **✓ Supports the vision** — directly advances a stated principle with suitable safeguards.
- **⚠ Partially supports the vision** — directionally aligned but incomplete, unvalidated, or carrying a material product-integrity risk.
- **✗ Conflicts with the vision** — would weaken a stated principle or proceed without a required product foundation.

## Executive Verdict

**Overall rating: ⚠ Partially supports the vision.**

The proposed implementation is thoughtful, trust-oriented, provider-neutral, privacy-aware, and deliberately incremental. Its modular-monolith approach, explicit audience consent model, provider boundaries, governed analytics, AI release gates, and staged milestones are broadly consistent with the Manifesto.

It is not ready for approval because:

1. the Product Blueprint and Audience Journey have not been reviewed;
2. several product-policy decisions are represented as architectural possibilities without an approved user proposition;
3. final moderation authority remains unresolved;
4. campaign targeting and cross-event audience intelligence could conflict with audience control;
5. the implementation plan is much more detailed about systems than about the direct audience experience and measurable customer outcomes.

The correct decision is **conditional continuation of product and architecture discovery, not implementation approval**.

## Alignment Summary

| Area | Rating | Product Guardian conclusion |
| --- | :---: | --- |
| Strategic traceability and approval readiness | ✗ | Full vision alignment cannot be established without the Product Blueprint and Audience Journey |
| Implementation principles | ✓ | Trust, simplicity, provider neutrality, consent, measurable quality, and governed AI are explicit |
| Domain model | ⚠ | Strong proposed concepts, but organisation relationships and audience identity require product validation |
| Bounded contexts | ✓ | Boundaries preserve ownership and allow incremental growth without premature service fragmentation |
| Services | ⚠ | Useful logical boundaries, but the service catalogue risks leading product scope before journeys are approved |
| APIs | ⚠ | Strong contract governance; audience and organisation APIs remain inferred rather than journey-led |
| Events | ✓ | Events are restrained, governed, privacy-minimized, and not confused with product value |
| Data model | ⚠ | Good separation and lineage, but broad data accumulation could exceed the audience value exchange |
| Permissions and privacy | ✓ | Least privilege, explicit tenant scope, audience control, and consent enforcement support trust |
| External integrations | ✓ | Provider-neutral design and independent product value strongly support the open-platform vision |
| Review verification | ✓ | Performance-level evidence and provenance directly support the trust pillar |
| Moderation and publication | ⚠ | History and appeals are proposed, but final independent publication authority is not resolved |
| Audience experience | ⚠ | Valuable audience capabilities exist, but appear later and are less defined than platform mechanics |
| Discovery | ✓ | Search, local discovery, audience library, and recommendations map clearly to the vision |
| Audience intelligence | ⚠ | Broadly aligned, but representativeness and the path from insight to action remain product risks |
| Engagement and campaign tools | ⚠ | Could improve relevance and growth, but may turn audience relationships into opaque targeting |
| AI services | ⚠ | Governance is strong, but most opportunities still require deterministic baselines and measurable demand |
| Milestone sequence | ⚠ | Foundation-first sequencing is safe, but direct user value must be validated and delivered earlier |
| Scale and maintainability | ✓ | Measured extraction, rebuildable projections, service objectives, and ADR gates protect maintainability |
| Ecosystem integrity | ✓ | TicketPal and Encore retain independent value while integrations are designed to strengthen both |

## Detailed Review

### 1. Strategic Traceability and Approval Readiness

**Rating: ✗ Conflicts with the vision if implementation proceeds now.**

**Principle affected:** Product Before Technology; Documentation First; Product Guardian; the required Strategic Review, Engineering Review, and Founder Approval sequence.

**Why it matters:** The implementation plan contains detailed domain, service, API, event, data, and AI proposals. Without the approved Product Blueprint and Audience Journey, there is no authoritative evidence that these proposals solve the agreed customer problems in the intended order. Proceeding would allow architecture to define product direction.

**Alternative approach:** Keep the plan at proposed status. Add the Product Blueprint and Audience Journey, map every capability and milestone to a named user problem and journey stage, record non-goals, and then complete Strategic Review before architecture decisions are approved.

**Guardian decision:** No implementation approval until the complete vision source set is available.

### 2. Implementation Principles

**Rating: ✓ Supports the vision.**

The plan's principles—Trust before Intelligence, modular monolith before distributed services, provider-neutral core, executable consent, secure tenant isolation, measured decomposition, governed AI, and decision traceability—translate the Manifesto into responsible delivery constraints.

They also support Simplicity Wins by resisting premature service extraction and AI adoption.

**Guardian condition:** These principles should remain acceptance criteria rather than introductory statements. A delivery proposal should show evidence of compliance.

### 3. Domain Model

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Audience Trust, Audience Intelligence, Audience Engagement, Open Platform, privacy creates trust.

**Partial-alignment concern:** The proposed audience, consent, discovery, intelligence, engagement, and AI concepts create a credible path beyond reviews. However, `Organisation` remains the current ownership root while co-productions, venues, promoters, touring companies, festivals, and shared rights may not fit one ownership tree. Audience account, identity-linking, and longitudinal profile concepts are proposed without the approved audience value exchange.

**Why it matters:** A domain model can silently dictate future product relationships. Incorrect ownership or identity assumptions become expensive to reverse and can create inappropriate data access or audience profiling.

**Alternative approach:** Validate the model with real ecosystem scenarios and the Audience Journey before adding new persistent concepts. Distinguish ownership, access, presentation, production, venue, provider, and data-controller relationships. Make audience identity-linking explicit, voluntary, reversible, and unnecessary for basic public discovery.

### 4. Bounded Contexts

**Rating: ✓ Supports the vision.**

The proposed contexts separate Provider Integration from Encore's core language, Audience from Engagement, Review Trust from publication, and operational truth from derived Intelligence and AI. This protects openness, privacy, and long-term maintainability.

The instruction that contexts begin as modules rather than separate deployables supports simplicity and prevents technology from becoming the product.

**Guardian condition:** Contexts must follow validated product capabilities. They are not themselves roadmap outcomes.

### 5. Services

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Maintainability, explicit responsibility, privacy, safe integration, action-oriented intelligence.

**Partial-alignment concern:** The service catalogue is comprehensive but more mature than the available product definition. Naming `CampaignService`, `AudienceSelectionService`, AI gateways, and forecasting services can create implementation momentum before the customer proposition and audience consent model are agreed.

**Why it matters:** Architectural completeness can be mistaken for product approval. This would conflict with Product Before Technology and Simplicity Wins.

**Alternative approach:** Treat service names beyond the current review platform as placeholders. Approve one end-to-end product capability at a time, then introduce only the minimum service boundary needed for that validated journey.

### 6. APIs

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Open Platform, provider neutrality, documentation first, secure boundaries.

**Partial-alignment concern:** Versioning, idempotency, pagination, scoped principals, and compatibility are appropriate. The proposed Audience and Organisation API families, however, assume account, profile, consent, insight, segment, campaign, and data-rights experiences not yet validated against the Product Blueprint and Audience Journey.

**Why it matters:** Public API contracts can lock product assumptions into long-lived external obligations.

**Alternative approach:** Approve APIs at capability boundaries after the user journey and data purpose are agreed. Begin with provider-neutral integration contracts and existing verified-review needs; defer broad audience and campaign APIs until their product experiences are validated.

### 7. Events

**Rating: ✓ Supports the vision.**

The plan correctly treats events as governed delivery mechanisms rather than customer value. Transactional outbox, at-least-once delivery, idempotent consumers, versioning, privacy minimization, and explicit replay protect trust and maintainability.

It also avoids treating audience telemetry as automatically collectible, which supports privacy and informed control.

**Guardian condition:** No event should exist solely because it may become useful. Every event needs an approved product or operational purpose and retention rule.

### 8. Data Model and Intelligence Foundation

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Trusted insight, action over dashboards, privacy, traceability, secure scaling.

**Partial-alignment concern:** Separating operational, analytical, search, feature, and model data is sound. The plan also proposes interaction histories, audience pseudonyms, feature storage, model artifacts, and longitudinal facts. Together these could encourage collecting data because it might be useful.

**Principle affected:** People are never the product; every data use must be transparent, ethical, and controlled.

**Why it matters:** The flywheel depends on audience trust. Broad behavioural collection without an immediate, understood user benefit would turn the trust proposition into surveillance.

**Alternative approach:** Establish an audience data charter before new signal collection. Require a named purpose, audience benefit, minimum necessary fields, retention, consent position, and deletion behavior for every signal. Start intelligence with existing verified and aggregate data.

### 9. Permissions and Privacy

**Rating: ✓ Supports the vision.**

The proposed RBAC/ABAC approach, field-level purpose checks, tenant scoping, restricted AI principals, stronger privileged access, deterministic consent enforcement, and audited high-impact actions are aligned with the Manifesto's privacy and trust promises.

Separating organisation analysts, moderators, campaign managers, provider principals, and AI workloads prevents one broad administrator role from accumulating inappropriate access.

**Guardian condition:** Organisations should receive useful insight and eligible reach, not unrestricted audience identities. Consent withdrawal must override cached segments, recommendations, analytics, and campaign plans.

### 10. External Integrations

**Rating: ✓ Supports the vision.**

The provider adapter boundary, scoped credentials, conformance testing, reconciliation, lifecycle management, and provider-neutral commands support Open Platform and ADR-000. TicketPal can remain the flagship integration without defining Encore's domain or preventing other providers.

**Guardian condition:** A new integration must demonstrate benefit to Encore, the provider, organisations, and audiences. Provider expansion should not precede proof that the core Encore audience and organisation experiences create standalone value.

### 11. Review Verification

**Rating: ✓ Supports the vision.**

Performance-level evidence, single-use eligibility, provenance, idempotency, and explicit separation of attendance evidence from invitation delivery strongly support Audience Trust.

**Guardian condition:** The public meaning of “verified attendance,” “verified review,” and “verified engagement” must be defined consistently. Verification should communicate evidence strength without implying that a subjective opinion is objectively true.

### 12. Moderation and Publication

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Moderation before publication, attributable decisions, history, appeals, abuse handling, and transparent review state.

**Partial-alignment concern:** The proposed plan improves governance but does not resolve whether an organisation being reviewed should have final authority to suppress a review about itself.

**Principle affected:** Trust is everything; Encore is an independent platform.

**Why it matters:** Organisation-controlled publication can bias public evidence and every score, recommendation, benchmark, or insight derived from it. An appeal after suppression may not be sufficient if audiences do not know their review was withheld.

**Alternative approaches:**

- Let organisations flag or respond, while Encore owns final publication policy.
- Permit organisation decisions for clear policy violations but route disputed or critical reviews to independent Encore moderation.
- Publish transparent moderation reasons and aggregate suppression statistics.
- Give audience members a clear status and appeal journey.

**Guardian decision:** Final moderation authority must be resolved in Strategic Review before expanding publication or analytics.

### 13. Audience Experience

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Personal history, favourites, watchlists, preferences, consent, discovery, review submission, and data control are represented.

**Partial-alignment concern:** The plan devotes considerably more definition to system boundaries than to the experience through which an audience member understands Encore's value. Audience identity arrives after provider and production foundations, and the proposed journey remains inferred.

**Why it matters:** The Manifesto promises a trusted companion, not merely an intelligence backend. Without early direct audience value, Encore risks collecting reviews for organisation benefit while the audience receives little in return.

**Alternative approach:** Use the approved Audience Journey to define the first complete audience loop: discover, decide, attend, verify, review, see contribution/history, and discover again. Deliver visible audience value before broadening profiling or organisation activation.

### 14. Discovery

**Rating: ✓ Supports the vision.**

The proposed progression from public catalogue and search to local discovery, venue/company discovery, favourites, watchlists, similar-show recommendations, and optional personalization directly supports the Manifesto.

The deterministic-first recommendation approach protects simplicity and avoids using AI for its own sake.

**Guardian condition:** Recommendation objectives must include diversity, novelty, accessibility, geographic fairness, and exposure for smaller organisations—not only clicks or ticket sales. Paid influence must be visibly separate.

### 15. Audience Intelligence

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Sentiment, recommendation rates, attendance behaviour, reach, repeat engagement, benchmarking, marketing effectiveness, audience growth, and actionable insight are represented.

**Partial-alignment concern:** The product validity of these measures depends on provider coverage, invitation delivery, response bias, moderation, consent, and cohort size. The plan proposes quality and lineage controls, but the business experience could still present weak data with excessive authority.

**Why it matters:** Incorrect intelligence is more harmful than no intelligence when organisations make creative or commercial decisions from it.

**Alternative approach:** Deliver descriptive metrics in stages. Show sample size, coverage, source period, quality, and known bias. Begin with an organisation's own trends before peer benchmarks or predictions. Validate whether each insight changes a real decision.

### 16. Engagement and Campaign Tools

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Better messages to the right people, less waste, stronger relationships, and ethical ticket growth.

**Partial-alignment concern:** Segment materialization, audience selection, and intelligent campaign tools can turn a trusted audience relationship into an organisation-targeting asset. Consent and suppression controls reduce but do not eliminate the conflict.

**Principles affected:** People are never the product; marketing should be intelligent rather than intrusive; audience control creates trust.

**Why it matters:** Campaign revenue can create pressure to maximize reachable audiences, message frequency, or conversion at the expense of autonomy and discovery diversity.

**Alternative approach:** Begin with audience-initiated follows, watchlists, and requested alerts. Let organisations communicate through Encore-defined eligibility and frequency rules without receiving raw audience identity. Separate recommendations, service messages, and direct marketing. Measure complaints, unsubscribes, trust, and discovery quality alongside conversion.

### 17. AI Services

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** AI is treated as an enabling capability, use cases require measurable value, human oversight exists, and deterministic fallbacks are required.

**Partial-alignment concern:** The implementation plan lists many possible AI services before data readiness and user demand are proven. Personalized ranking, prediction, campaign suggestions, and generative content create materially different risks and should not share a generic “AI platform” justification.

**Why it matters:** Building shared AI infrastructure too early would allow technology to dictate product direction and could create pressure to use models simply because they are available.

**Alternative approach:** Follow the [AI Product Lead Review](AI-Product-Lead-Review.md). Establish governance, then test one bounded theme-classification use case against a deterministic baseline. Approve later uses independently only when measurable incremental value, privacy, groundedness, diversity, and suspension criteria are met.

**Guardian decision:** AI platform investment is not approved as a standalone initiative. Only individually approved use cases may justify shared capability over time.

### 18. Milestone Sequence

**Rating: ⚠ Partially supports the vision.**

**Principles supported:** Milestone 0 requires blueprint reconciliation; trust and operations precede intelligence and AI; high-risk capabilities are delayed.

**Partial-alignment concern:** The sequence could spend substantial time on provider and platform mechanics before audiences experience clear standalone value. Milestones are capability-heavy and do not specify measurable customer outcomes or a thin end-to-end audience loop.

**Why it matters:** A technically mature platform without validated audience adoption would not fulfill the vision or strengthen the ecosystem.

**Alternative approach:** After the minimum production and trust baseline, deliver a thin complete audience journey in parallel with provider hardening. Define exit gates using both product outcomes and operational evidence. Do not progress solely because infrastructure is complete.

### 19. Scale and Maintainability

**Rating: ✓ Supports the vision.**

The modular-monolith default, measured extraction, explicit contexts, versioned contracts, rebuildable projections, governed migrations, service objectives, recovery, and ADR requirements protect long-term coherence without premature complexity.

**Guardian condition:** Scale architecture must respond to measured demand. “Central platform” ambition alone does not justify distributed systems, feature stores, warehouses, or specialized AI infrastructure.

### 20. Ecosystem Integrity

**Rating: ✓ Supports the vision.**

The plan preserves Encore identities, isolates provider contracts, retains standalone product value, supports scoped integrations, and creates a path to additional providers. This directly supports Ecosystem Thinking, Open Platform, and ADR-000.

**Guardian condition:** TicketPal should receive flagship integration quality, not privileged control of Encore's roadmap, domain, audience relationship, or provider participation rules.

## Conflicts Requiring Resolution

### Conflict 1 — Implementation before complete product definition

- **Principle affected:** Product Before Technology; Documentation First.
- **Why it matters:** Product intent could be inferred from architecture rather than approved customer outcomes.
- **Alternative:** Complete and approve the Product Blueprint and Audience Journey, map the plan to them, then repeat Product Guardian review.

### Conflict 2 — Unresolved final moderation authority

- **Principle affected:** Trust is everything; independent platform.
- **Why it matters:** Reviewed organisations may influence which criticism becomes public and which signals feed intelligence.
- **Alternative:** Encore-owned publication policy, independent escalation, transparent reasons, audience status, and appeals.

### Conflict 3 — Potential audience commoditization through campaigns

- **Principle affected:** People are never the product; privacy creates trust; marketing should not be intrusive.
- **Why it matters:** Commercial pressure may expand profiling, audience access, contact frequency, and optimization beyond audience expectations.
- **Alternative:** Audience-initiated follows first, purpose-specific consent, Encore-controlled eligibility, no raw audience export by default, and trust guardrails equal to conversion measures.

### Conflict 4 — AI infrastructure before proven AI value

- **Principle affected:** AI is not the product; Simplicity Wins; Product Before Technology.
- **Why it matters:** Platform investment can create a need to find uses for AI rather than solve validated problems.
- **Alternative:** One bounded use case at a time, deterministic baseline, measurable value gate, and shared infrastructure only after repeated justified demand.

## Required Changes Before Approval

1. Add and approve `Encore-Product-Blueprint.md` and `Audience-Journey.md`.
2. Map every milestone to an exact vision objective, user journey stage, user benefit, product measure, and explicit non-goal.
3. Resolve final moderation and publication authority.
4. Approve the audience value exchange, identity, consent, communication, and data-rights model.
5. Define which audience signals may be collected and which are prohibited or deferred.
6. Reframe campaign tools around audience-initiated relationships and controlled access.
7. Remove any implication that an AI platform is approved independently of validated AI use cases.
8. Add audience trust, discovery, diversity, organisation decision value, and ecosystem value to milestone exit criteria.
9. Deliver a thin end-to-end audience value loop before broad intelligence or engagement expansion.
10. Complete Strategic Review, Engineering Review, Product Guardian reassessment, and Founder Approval.

## Product Guardian Decision

**Decision: Do not approve implementation yet.**

The proposed plan is a strong architecture hypothesis and is suitable for continued product and engineering review. It is not yet an approved implementation baseline because complete product traceability and several high-impact trust decisions remain unresolved.

Approval should be reconsidered when the required product documents are available and the conflicts above have explicit decisions. Long-term product integrity should take priority over converting a comprehensive technical plan into delivery prematurely.
