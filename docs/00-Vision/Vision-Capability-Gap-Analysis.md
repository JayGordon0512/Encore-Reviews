# Encore Vision Capability Gap Analysis

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 0.2

**Date:** 24 July 2026

**Perspective:** Product analysis

**Status:** Advisory — roadmap validation required

## Purpose

This document identifies capabilities described by the Encore vision that are not yet represented in the current implemented product architecture. It is a product analysis only: it does not prescribe technical design or authorize implementation.

The requested source set was:

- [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md)
- [Encore Product Blueprint](Encore-Product-Blueprint.md)
- [Audience Journey](Audience-Journey.md)

The findings have been reconciled with the authoritative vision sources and compared with the current product baseline described by the [Encore Reviews Platform Charter](Encore-Platform-Charter.md), [Vision and Product Boundaries](README.md), and current architecture documentation. They remain advisory until approved gaps are translated into the authoritative roadmap.

## Prioritization Method

- **Critical:** Required to establish the trust, permission, evidence, and data foundation on which the vision depends.
- **High:** Delivers a major audience or organisation promise and is central to the Encore flywheel.
- **Medium:** Increases usefulness, adoption, retention, or commercial value after the principal platform capabilities exist.
- **Future:** Strategically relevant but dependent on sufficient trusted data, governance, adoption, and evidence of value.

Suggested implementation order is expressed across the whole analysis. Items at the same stage may be shaped in parallel, but product validation should precede delivery.

## Table of Contents

- [Critical](#critical)
- [High](#high)
- [Medium](#medium)
- [Future](#future)
- [Product-Level Sequencing Summary](#product-level-sequencing-summary)
- [Validation Required](#validation-required)

## Critical

### 1. Audience Identity, Consent, and Trust Centre

**Vision capability:** Personal recommendations, favourite venues and companies, watchlists, personal review history, repeat-attendance insight, demographic insight with explicit consent, and audience control over information use.

**Current gap:** The current product has a pseudonymized `Reviewer` record associated with review submission, but no audience account, durable preference profile, consent history, communication controls, account-linking experience, or audience-facing data controls.

**Why it matters:** Most of the vision assumes that Encore can recognize an audience member over time and use their information for clearly understood purposes. Without a trusted audience identity and control experience, personalization, engagement, and longitudinal intelligence would either be impossible or would operate in ways audiences cannot see or control.

**Which user benefits:** Audience members benefit from continuity, relevant experiences, and meaningful control. Organisations benefit from higher-quality, permissioned insight. Encore benefits from a defensible trust relationship and more reliable first-party data.

**Suggested implementation order:** 1. Define the value exchange, account model, consent purposes, preference categories, participation choices, and audience trust experience before launching personalization or direct engagement.

**Dependencies:** Approved Audience Journey; privacy and data-use policy; audience account proposition; age and jurisdiction decisions; identity-linking rules; retention, deletion, correction, and portability policies.

**Expected business value:** Establishes the permissioned audience relationship required for retention, personalization, repeat engagement, and differentiated audience intelligence. Reduces regulatory and reputational risk that could otherwise prevent platform growth.

### 2. Verifiable Attendance and Engagement Evidence

**Vision capability:** Verified attendance, verified reviews, and verified engagement as the basis of trusted intelligence.

**Current gap:** Review invitations provide single-use eligibility linked to a performance, but the current architecture does not establish a general attendance-evidence lifecycle or define what “verified engagement” means. Provider-supplied invitation creation is not, by itself, a complete product definition of attendance verification.

**Why it matters:** Trust is the Manifesto's primary differentiator. If different providers, organisations, or channels apply inconsistent evidence standards, review labels and derived insight will become difficult to explain and compare.

**Which user benefits:** Audiences receive more credible reviews and recommendations. Organisations receive insight based on understandable evidence. Providers receive a clear participation standard. Encore protects the credibility of its brand.

**Suggested implementation order:** 2. Define evidence classes, confidence levels, source provenance, exceptions, correction, and the product meaning of verified attendance, review, and engagement before broadening integrations or intelligence.

**Dependencies:** Provider agreements; approved attendance and eligibility policy; fraud and abuse policy; performance identity; dispute and correction journeys; clear public labeling.

**Expected business value:** Strengthens Encore's primary market differentiation, improves insight quality, and creates a consistent trust contract that can scale across providers.

### 3. Independent Review Trust and Moderation Governance

**Vision capability:** Trusted reviews and trusted audience experiences.

**Current gap:** Reviews are moderated, but organisation administrators currently approve or reject reviews about their organisation's performances. The current product does not represent an independent publication policy, moderation history visible at product level, audience appeal path, disputed-content process, or transparency standard.

**Why it matters:** Verification proves that an eligible person can submit a review; it does not ensure fair publication. A platform presented as independent could lose audience confidence if reviewed organisations can suppress legitimate criticism without oversight.

**Which user benefits:** Audience members benefit from fair treatment and credible public content. Organisations benefit from consistent rules and protection against abuse. Encore benefits from a defensible trust proposition.

**Suggested implementation order:** 3. Establish publication standards, decision ownership, mandatory reasons, escalation and appeals, abuse handling, and transparency measures before substantially increasing review volume or using reviews for intelligence.

**Dependencies:** Product trust policy; legal/content policy; audience and organisation journeys; operating ownership; support and escalation model; moderation quality measures.

**Expected business value:** Protects review credibility, reduces churn and disputes, and increases confidence in every downstream score, recommendation, benchmark, and insight.

### 4. Permissioned Audience Signal and Data-Quality Foundation

**Vision capability:** Learning from reviews, recommendations, return visits, first-time attendance, shared experiences, sentiment, attendance behaviour, audience growth, and marketing effectiveness.

**Current gap:** The current product records reviews and limited provider context but does not represent a product-wide interaction vocabulary, purpose-specific collection policy, audience-visible controls, data-quality measures, or rules for determining when a signal is sufficiently representative to support insight.

**Why it matters:** The intelligence promise cannot be delivered reliably from review content alone. At the same time, collecting every possible interaction would conflict with the Manifesto's privacy commitment. Product policy must specify which signals create legitimate value and how quality and representativeness are communicated.

**Which user benefits:** Organisations benefit from more accurate and understandable insight. Audiences benefit from better recommendations without uncontrolled tracking. Encore benefits from a coherent, reusable intelligence foundation.

**Suggested implementation order:** 4. Define the minimum audience-signal catalogue, allowed purposes, quality rules, opt-in or opt-out behavior, and success measures before creating intelligence dashboards or recommendation experiments.

**Dependencies:** Audience identity and consent; approved metric definitions; provider evidence; privacy policy; audience research; data quality and minimum-sample standards.

**Expected business value:** Makes future intelligence credible, reduces wasted product development, and creates a common measurement foundation for discovery, engagement, and organisation outcomes.

### 5. Provider-Neutral Ecosystem Participation

**Vision capability:** A central audience-intelligence platform for live entertainment, covering discovery, attendance, verified experience, and the full flywheel.

**Current gap:** The architecture is designed to keep the core provider-neutral, but TicketPal is the only implemented provider integration. There is no current product capability for onboarding, configuring, monitoring, or reconciling multiple provider relationships.

**Why it matters:** A single-provider data source limits audience coverage, organisation adoption, cross-market insight, and the strength of the flywheel. It also risks making “platform for live entertainment” broader than the product's practical reach.

**Which user benefits:** Organisations gain choice and broader coverage. Audiences can use Encore across more performances and venues. Providers gain a consistent partnership model. Encore expands its addressable market and reduces dependency.

**Suggested implementation order:** 5. Define the provider participation proposition, onboarding journey, data responsibilities, service expectations, and conformance standard; then validate it with a second provider before generalizing further.

**Dependencies:** Verified-evidence definitions; organisation and performance identity; provider commercial agreements; integration governance; data-controller responsibilities; support model.

**Expected business value:** Broadens market reach, improves data coverage, reduces concentration risk, and increases the network value of Encore to audiences and organisations.

## High

### 6. Search, Local Discovery, and Venue Discovery

**Vision capability:** Show discovery, venue discovery, and “What's On Near You.”

**Current gap:** The current product has a public show directory and show pages but no represented search, filtering, distance-aware discovery, rich venue discovery, location preference, or audience-oriented browse journeys.

**Why it matters:** Discovery is the audience-facing entry point to the flywheel. A directory alone becomes difficult to use as catalogue volume grows and cannot fulfill the promise of helping the right audience find the right performance.

**Which user benefits:** Audiences find relevant performances with less effort. Venues and organisations gain qualified exposure. Encore gains repeat visits and a stronger route from review content to ticket purchase.

**Suggested implementation order:** 6. Start with high-quality catalogue search and filters, then venue pages and user-controlled local discovery. Establish an effective non-personalized discovery baseline before applying AI.

**Dependencies:** Complete catalogue and performance data; venue identity and location quality; accessibility requirements; audience research; ticket-link attribution; content freshness.

**Expected business value:** Increases discoverability, ticket referrals, catalogue utility, organic acquisition, and the amount of audience interaction available to improve later recommendations.

### 7. Audience Library: Favourites, Follows, Watchlists, and Review History

**Vision capability:** Favourite venues, favourite companies, watchlists, and personal review history.

**Current gap:** None of these durable audience features is represented in the current product experience.

**Why it matters:** These features give audiences immediate value without requiring sophisticated prediction. They also capture explicit preferences, which are more transparent and easier to control than inferred interests.

**Which user benefits:** Audiences gain continuity and a reason to return. Organisations and venues gain a permissioned following. Encore gains retention and high-quality preference signals.

**Suggested implementation order:** 7. Deliver review history first, followed by watchlists and favourites/follows, with clear privacy defaults and controls.

**Dependencies:** Audience accounts; stable organisation, venue, show, and performance identities; preference controls; archived-content behavior; notification choices.

**Expected business value:** Improves registration value, retention, repeat sessions, and future recommendation quality while establishing a direct audience relationship.

### 8. Personalized and Similar-Show Recommendations

**Vision capability:** Personal recommendations, similar-show recommendations, and helping audiences discover performances they genuinely love.

**Current gap:** The current product has no recommendation experience, explanation, feedback loop, or audience control. It also lacks the explicit preference and interaction foundation needed for responsible personalization.

**Why it matters:** Recommendations are a central audience benefit and a key link between intelligence and ticket discovery. However, premature personalization can appear irrelevant, intrusive, or biased and can weaken trust.

**Which user benefits:** Audiences receive more relevant discovery. Organisations gain exposure to interested audiences. Smaller or unfamiliar productions can reach people beyond existing followers when diversity is an explicit objective.

**Suggested implementation order:** 8. Begin with transparent, non-personalized similar-show recommendations based on catalogue and trusted aggregate signals. Add opt-in personalization only after the audience library and consent foundation are established.

**Dependencies:** Search and catalogue quality; audience preferences; approved interaction signals; recommendation objectives; diversity and fairness policy; feedback and explanation experience; adequate coverage.

**Expected business value:** Increases discovery depth, ticket referrals, repeat use, and cross-catalogue exposure while differentiating Encore from static review sites.

### 9. Organisation Audience-Intelligence Workspace

**Vision capability:** Audience sentiment, geographic reach, attendance behaviour, recommendation rates, repeat attendance, marketing effectiveness, audience growth, and actionable intelligence.

**Current gap:** Organisation administrators currently have review activity, scores, and moderation capabilities but no audience-intelligence product, governed metrics, trends, cohorts, comparisons, or insight quality indicators.

**Why it matters:** The Manifesto states that reviews are the mechanism, not the product. Without an organisation intelligence experience, Encore remains primarily a review platform and does not deliver its principal business-facing differentiation.

**Which user benefits:** Producers, organisers, and venues gain evidence for programming, audience development, experience improvement, and marketing decisions. Encore gains a clearer organisation value proposition and potential recurring revenue driver.

**Suggested implementation order:** 9. Start with descriptive, directly explainable measures using trusted existing data. Add trends and cohorts only when definitions, sample thresholds, and quality indicators are established.

**Dependencies:** Permissioned signal foundation; verified evidence; metric definitions; organisation access model; data-quality standards; audience privacy and disclosure thresholds; user research with decision-makers.

**Expected business value:** Converts trusted review activity into decision support, strengthens organisation retention, and establishes the foundation of a differentiated business product.

### 10. Audience Engagement and Follow-Based Notifications

**Vision capability:** Weekly discovery updates, favourite venue alerts, favourite company updates, and helping organisations engage their communities.

**Current gap:** The current product does not represent audience notification preferences, follows, channel selection, communication history, suppression, frequency controls, or audience-facing engagement journeys.

**Why it matters:** Timely follow-based communication can create repeat discovery without requiring opaque targeting. Without strong controls, however, it can become the intrusive marketing the Manifesto explicitly rejects.

**Which user benefits:** Audiences receive updates they have requested. Organisations and venues strengthen relationships with interested people. Encore gains repeat engagement and measurable discovery opportunities.

**Suggested implementation order:** 10. Begin with audience-controlled alerts for explicit follows and watchlists. Introduce curated weekly discovery only after frequency, preference, and unsubscribe experiences are proven.

**Dependencies:** Audience accounts; favourites and follows; event freshness; communication consent; channel and frequency preferences; suppression and deliverability policy; content selection rules.

**Expected business value:** Improves retention, return attendance, ticket referrals, and the perceived value of following organisations through Encore.

## Medium

### 11. Production and Audience Benchmarking

**Vision capability:** Production benchmarking and comparative audience understanding.

**Current gap:** The current product has no benchmark definitions, comparable cohorts, normalization rules, disclosure thresholds, or organisation experience for interpreting comparison.

**Why it matters:** Benchmarks can make metrics actionable by providing context, but invalid comparisons can mislead organisations and damage trust. Genre, venue size, geography, price, run length, audience coverage, and sample bias can materially affect results.

**Which user benefits:** Organisations and producers understand performance relative to relevant peers or their own history. Encore can provide value beyond raw dashboards.

**Suggested implementation order:** 11. Start with an organisation's own historical comparison. Introduce anonymized peer benchmarks later, only for cohorts with sound comparability and sufficient coverage.

**Dependencies:** Stable metric catalogue; sufficient multi-organisation data; quality and representativeness standards; cohort policy; anonymization and disclosure rules; organisation research.

**Expected business value:** Increases the actionability and perceived value of intelligence, supporting premium organisation propositions and longer-term retention.

### 12. Geographic and Consented Demographic Insight

**Vision capability:** Geographic reach and demographic insight where users have provided explicit consent.

**Current gap:** The current product does not represent audience demographic collection, consent journeys for these purposes, geographic resolution policy, cohort suppression, or an organisation-facing insight experience.

**Why it matters:** These insights can support access, outreach, and audience development, but they carry elevated privacy, fairness, representativeness, and re-identification risks. Collecting them before a clearly valuable use exists would conflict with data minimization.

**Which user benefits:** Organisations can identify underserved communities and evaluate reach. Audiences may benefit from more relevant and inclusive programming when insight is used responsibly.

**Suggested implementation order:** 12. Validate specific organisation decisions first. Pilot only the minimum voluntarily supplied attributes required for those decisions, starting with coarse geographic reach before sensitive demographic analysis.

**Dependencies:** Explicit consent and audience explanation; approved questions and purposes; minimum cohort rules; accessibility and inclusion review; data-quality analysis; correction and deletion controls.

**Expected business value:** Supports evidence-led audience development and funding or outreach narratives, while differentiating Encore's organisation insight when coverage is representative.

### 13. Marketing Effectiveness Measurement

**Vision capability:** Marketing effectiveness, better targeting, less waste, stronger communities, and more ticket sales.

**Current gap:** The current product includes ticket links but does not represent campaign attribution, discovery-to-purchase outcomes, agreed effectiveness measures, or a clear distinction between Encore-driven discovery and external marketing activity.

**Why it matters:** Encore cannot credibly claim improved marketing or ticket outcomes without measurable evidence. Poor attribution may overstate value, encourage excessive tracking, or optimize short-term conversion at the expense of audience trust.

**Which user benefits:** Organisations understand which activity helps audiences discover and attend. Audiences benefit when messaging becomes more relevant and less frequent. Encore can demonstrate commercial impact.

**Suggested implementation order:** 13. Define a privacy-conscious measurement framework for Encore referrals and explicit campaigns before building intelligent campaign tools. Use aggregate outcomes where individual attribution is unnecessary.

**Dependencies:** Ticket referral tracking; provider outcome feedback; campaign definitions; consent and attribution policy; baseline metrics; agreed conversion windows; data quality.

**Expected business value:** Demonstrates return on investment, strengthens organisation sales and retention, and informs product investment with outcome evidence.

### 14. Action-Oriented Insight and Decision Guidance

**Vision capability:** Every insight should answer, “What should I do next?”

**Current gap:** Current organisation data is presented as activity and scores. The product does not represent decision goals, recommended actions, evidence strength, feedback on actions taken, or outcome learning.

**Why it matters:** More dashboards would not fulfill the Manifesto. Insight becomes valuable only when it supports a real decision and communicates why a suggested action is relevant.

**Which user benefits:** Organisation users gain clarity and save analysis time. Encore gains adoption and differentiation because its intelligence is tied to outcomes rather than data volume.

**Suggested implementation order:** 14. Identify a small set of recurring organisation decisions, then design insight-to-action journeys using deterministic guidance before considering AI-generated advice.

**Dependencies:** Organisation user research; governed metrics; evidence and confidence presentation; outcome tracking; role-specific needs; clear separation between evidence and recommendation.

**Expected business value:** Improves feature adoption, time to value, decision confidence, and the likelihood that organisations renew or expand their use of Encore.

### 15. Intelligent Campaign Tools

**Vision capability:** Intelligent campaign tools that connect organisations with people most likely to attend.

**Current gap:** No audience segmentation, campaign creation, approval, targeting, frequency governance, delivery, or campaign-result capability is represented in the current product.

**Why it matters:** Campaign tools could close the loop between insight and ticket sales, but they also create the clearest tension with the promise that people are not the product. Audience access and organization control must be deliberately bounded.

**Which user benefits:** Organisations can reach interested audiences efficiently. Audiences can receive more relevant communications if participation is voluntary and controllable. Encore gains a measurable commercial product.

**Suggested implementation order:** 15. Begin with organization-owned followers and explicit interest groups. Add suggested segments only after consent enforcement, frequency policy, measurement, and audience trust controls have demonstrated effectiveness.

**Dependencies:** Audience engagement foundation; organisation entitlements; consent at send time; suppression and frequency controls; segmentation policy; marketing effectiveness measures; provider or channel partnerships.

**Expected business value:** Creates potential campaign revenue, increases ticket referrals, and strengthens organisation relationships while reducing indiscriminate messaging.

## Future

### 16. Predictive Audience Insight

**Vision capability:** Predictive insight and early identification of changing audience behaviour.

**Current gap:** The current product does not represent prediction use cases, sufficient longitudinal data, confidence and uncertainty, validation baselines, decision ownership, or monitoring of prediction outcomes.

**Why it matters:** Prediction may help organisations act earlier, but unreliable forecasts can cause financial, creative, or audience-development harm. The capability is valuable only after descriptive insight is trusted and decisions are clearly defined.

**Which user benefits:** Organisations and producers may improve planning, programming, scheduling, and outreach. Encore may gain a differentiated premium intelligence proposition.

**Suggested implementation order:** 16. Pilot one narrow, reversible prediction only after sufficient data history and successful descriptive insight. Compare it with a simple baseline and require uncertainty to be visible.

**Dependencies:** Longitudinal, representative data; stable metrics; defined decision and intervention; governance and monitoring; sufficient sample size; outcome feedback; human oversight.

**Expected business value:** Potentially improves planning quality and creates premium value, but only where measurable performance exceeds simple alternatives.

### 17. AI-Generated Audience Intelligence

**Vision capability:** Transforming thousands of individual audience experiences into clear recommendations and identifying trends before people notice them.

**Current gap:** The current product has no governed AI insight experience, evidence-linked summaries, quality evaluation, correction path, uncertainty communication, or ownership for model-supported conclusions.

**Why it matters:** AI can reduce the effort required to interpret large volumes of feedback, but fabricated, biased, or decontextualized conclusions would undermine the trust foundation.

**Which user benefits:** Organisation decision-makers can understand large feedback volumes more quickly. Encore can scale insight without requiring manual analysis of every review.

**Suggested implementation order:** 17. Begin with assistive theme grouping or summaries over approved, sufficiently large cohorts. Keep source evidence accessible and require human review before using outputs for material decisions.

**Dependencies:** Trusted review corpus; moderation governance; analytical foundation; AI use-case policy; evaluation criteria; evidence and correction experience; data-use rights; ongoing quality monitoring.

**Expected business value:** Reduces analysis time, increases insight usability, and supports scale, provided trust and accuracy are demonstrably maintained.

### 18. AI-Powered Audience Engagement

**Vision capability:** Future AI-powered audience engagement and intelligent matching of audiences to performances.

**Current gap:** The current product has neither the governed campaign foundation nor the audience, consent, recommendation, outcome, and AI-control capabilities required for autonomous or semi-automated engagement.

**Why it matters:** This capability could improve relevance and ticket conversion, but it combines personal data, prediction, communication, and commercial influence. It therefore has the greatest potential to become intrusive or discriminatory.

**Which user benefits:** Audiences may receive timely, relevant discovery. Organisations may reduce wasted marketing and reach likely attendees. Encore may create a scalable engagement proposition.

**Suggested implementation order:** 18. Consider only after audience-controlled engagement and intelligent campaign tools are mature. Start with AI suggestions that require organisation approval; do not begin with autonomous audience targeting or sending.

**Dependencies:** All Critical foundations; personalized discovery; campaign governance; effectiveness measurement; AI governance; bias and diversity standards; explanation and opt-out; incident and suspension processes.

**Expected business value:** Potentially increases campaign efficiency, discovery, and ticket sales at scale, but should remain conditional on proven incremental value and no unacceptable loss of audience trust.

## Product-Level Sequencing Summary

| Sequence | Capability | Priority | Product outcome unlocked |
| --- | --- | --- | --- |
| 1 | Audience identity, consent, and trust centre | Critical | Permissioned audience relationship |
| 2 | Verifiable attendance and engagement evidence | Critical | Consistent trust claim |
| 3 | Independent review trust and moderation governance | Critical | Credible published feedback |
| 4 | Permissioned audience signal and data-quality foundation | Critical | Reliable basis for intelligence |
| 5 | Provider-neutral ecosystem participation | Critical | Broader coverage and reduced dependency |
| 6 | Search, local discovery, and venue discovery | High | Useful audience acquisition journey |
| 7 | Audience library | High | Retention and explicit preferences |
| 8 | Recommendations | High | Relevant discovery and cross-catalogue exposure |
| 9 | Organisation intelligence workspace | High | Core organisation value proposition |
| 10 | Follow-based notifications | High | Repeat audience engagement |
| 11 | Benchmarking | Medium | Contextual organisation decisions |
| 12 | Geographic and demographic insight | Medium | Audience-development evidence |
| 13 | Marketing effectiveness | Medium | Demonstrable commercial outcomes |
| 14 | Action-oriented insight | Medium | Faster decision value |
| 15 | Intelligent campaign tools | Medium | Governed audience activation |
| 16 | Predictive insight | Future | Earlier planning signals |
| 17 | AI-generated intelligence | Future | Scaled interpretation |
| 18 | AI-powered engagement | Future | Scaled, optimized activation |

This sequence should not be read as a commitment to deliver all capabilities. It is the safest dependency order for evaluating them. Product discovery may reject, narrow, combine, or reprioritize an item.

## Validation Required

Before this analysis is approved:

1. Compare every finding with the missing Product Blueprint and remove capabilities that are illustrative rather than committed.
2. Map each retained capability to the relevant Audience Journey stages, user needs, trust moments, and recovery paths.
3. Confirm the relative priority from audience, organisation, provider, commercial, legal, and operational perspectives.
4. Define measurable product outcomes and explicit non-goals for the first implementation horizon.
5. Confirm which capabilities are platform-wide, organisation entitlements, audience options, or future experiments.
6. Resolve conflicts between commercial optimization, discovery diversity, audience control, and review independence before roadmap commitment.
