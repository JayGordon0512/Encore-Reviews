# Encore AI Product Lead Review

**Version:** 0.2

**Date:** 24 July 2026

**Perspective:** AI Product Lead

**Status:** Advisory — use-case and data-readiness validation required

## Purpose

This review evaluates every AI capability currently proposed across [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md), [Platform Strategy](Platform-Strategy.md), the [Encore Product Blueprint](Encore-Product-Blueprint.md), the [Audience Journey](Audience-Journey.md), the [Operating Principles](Operating-Principles.md), the [Engineering Implementation Plan](Encore-Engineering-Implementation-Plan.md), the [Vision Capability Gap Analysis](Vision-Capability-Gap-Analysis.md), and the [Roadmap Vision Alignment Review](../06-Roadmap/Roadmap-Vision-Alignment-Review.md).

Its purpose is to ensure AI is used only where it produces measurable value beyond deterministic software. It is a product assessment, not approval to build, buy, train, or deploy any model.

Any significant AI initiative must complete Strategic Review, Engineering Review, and Founder Approval. It must demonstrably improve Discovery, Insight, Decision-making, or Audience experience as required by the Operating Principles.

The review has been reconciled with the authoritative Product Blueprint and Audience Journey. Every AI opportunity remains subject to individual product purpose, data-readiness, deterministic-baseline, privacy, evaluation, and measurable-value approval.

## Table of Contents

- [Decision Standard](#decision-standard)
- [Executive Conclusion](#executive-conclusion)
- [Opportunity Summary](#opportunity-summary)
- [Immediate](#immediate)
- [Near Term](#near-term)
- [Long Term](#long-term)
- [Moonshot](#moonshot)
- [Capabilities That Should Remain Deterministic](#capabilities-that-should-remain-deterministic)
- [AI Product Release Gates](#ai-product-release-gates)
- [Recommended AI Portfolio Sequence](#recommended-ai-portfolio-sequence)

## Decision Standard

AI should be used only when all of the following are true:

1. A specific user problem and decision are understood.
2. A deterministic or manual baseline exists.
3. AI can plausibly improve a measurable outcome beyond that baseline.
4. The necessary data is lawful, representative, sufficiently large, and of known quality.
5. The output can be evaluated before release and monitored afterward.
6. Incorrect output has a bounded consequence and a safe fallback.
7. Users receive transparency, control, and human recourse appropriate to the impact.
8. Privacy, bias, diversity, security, and commercial-influence risks are acceptable.
9. The capability can be suspended without weakening core review, discovery, or organisation workflows.
10. Model and operating cost remain lower than the demonstrated value created.

“AI” includes machine learning, statistical learning, recommender systems, natural-language models, and generative models. Conventional analytics, business rules, search filters, aggregate calculations, and workflow automation are not classified as AI merely because they feel intelligent to the user.

## Executive Conclusion

Encore should not begin with a platform-wide AI layer. The current product does not yet have the audience identity, consent, interaction, analytical-quality, model-governance, or outcome-measurement foundations required for safe personalization, prediction, or AI-powered engagement.

The strongest first AI opportunity is **review theme classification**, followed by **evidence-linked review summarization** once review volume makes manual interpretation costly. Both can be bounded to approved review content, evaluated against human-labelled examples, and kept advisory.

Recommendations should start as deterministic or conventional ranking products. AI becomes justified only when it demonstrably improves relevance without reducing diversity, accessibility, audience control, or exposure for smaller organisations.

Autonomous targeting or communication is not recommended. AI may eventually suggest segments, actions, or campaign content, but an authorized person should remain accountable for commercial and audience-impacting decisions.

## Opportunity Summary

| Priority | AI capability | Should AI be used? | Deterministic-first position | Hallucination risk | Product decision |
| --- | --- | --- | --- | --- | --- |
| Immediate | Review theme classification | Conditional yes | Controlled taxonomy and manual tagging baseline | Low | Run a bounded offline pilot when volume justifies it |
| Near Term | Evidence-linked review summaries | Conditional yes | Filters, excerpts, counts, and curated templates first | High | Pilot only with citations and human correction |
| Near Term | Sentiment interpretation | Limited use | Ratings, recommendation rates, tags, and lexical baseline first | Medium | Use as supporting signal, never audience truth |
| Near Term | Similar-show recommendations | Conditional | Metadata and rule-based ranking first | Low | Add learned ranking only after baseline measurement |
| Near Term | Trend and anomaly detection | Conditional yes | Thresholds, rolling averages, and statistical control rules first | Low | Use for advisory alerts with visible evidence |
| Long Term | Personalized discovery | Conditional yes | Explicit preferences and deterministic filters first | Low | Opt-in only after identity, consent, and diversity controls |
| Long Term | Action-oriented insight suggestions | Limited use | Decision trees and playbooks first | High | AI may draft options; humans own decisions |
| Long Term | Attendance or demand forecasting | Conditional yes | Seasonal and historical statistical baseline first | Low | Proceed only for a narrow, reversible decision |
| Long Term | Campaign audience suggestions | Generally defer | Explicit-follow and rule-based eligibility first | Medium | Advisory only after campaign governance matures |
| Long Term | Generative campaign assistance | Conditional yes | Approved templates and factual content blocks first | High | Drafting only; mandatory human approval |
| Moonshot | Conversational audience-intelligence copilot | Conditional, much later | Guided reports and structured questions first | High | Evidence-linked interface only after mature intelligence |
| Moonshot | Autonomous AI-powered engagement | No under current principles | Rules, consent checks, approved campaigns, and human decisions are better | High | Do not permit autonomous targeting or sending |

Priority means the recommended horizon for product discovery and validation, not an implementation date.

## Immediate

Immediate work should establish one narrow learning opportunity and the governance required to say “no” to unsuitable AI. No customer-facing AI should be treated as immediately production-ready.

### 1. Review Theme Classification

**Proposed use:** Group approved review text into recurring themes so organisation users can understand large feedback volumes.

**Should AI be used?** Conditional yes. Text classification is a credible AI use when review volume, varied language, and evolving themes make manual or rule-based categorization costly. It should begin as an assistive classification, not an authoritative interpretation of audience intent.

**Could deterministic software solve this better?** At low volume, yes. A controlled tag taxonomy, structured review questions, keyword rules, and manual analysis are cheaper, more explainable, and easier to correct. AI is justified only if it materially improves coverage or analyst time while meeting quality thresholds.

**What data would be required?** Approved review text; language; performance/show context; existing audience-selected tags; a versioned theme taxonomy; representative human-labelled examples; correction feedback. Ratings or recommendation choices may support evaluation but should not be used to force text into a positive or negative interpretation.

**Privacy considerations:** Reviews may contain names, health information, protected traits, or details about other people. Inputs require minimization and redaction policy. Model providers must not retain or train on Encore data without explicit approval. Tenant data must not leak through training examples, outputs, logs, or evaluation tools.

**Risk of hallucination:** Low for constrained classification, but misclassification remains material. The system must choose only approved labels, support “unclear/other,” show sample evidence, and avoid inventing themes not supported by text.

**Expected business value:** Reduces time spent reading large review sets, improves navigation of feedback, and creates a foundation for evidence-linked insight. Measure label agreement with human reviewers, correction rate, time saved, coverage, and organisation adoption.

**Priority decision:** Immediate offline product experiment; production only after the deterministic baseline and minimum review-volume threshold are established.

## Near Term

Near-term opportunities become credible after review governance, a metric catalogue, sufficient approved content, and an AI evaluation process exist.

### 2. Evidence-Linked Review Summaries

**Proposed use:** Transform many individual audience experiences into concise summaries for organisation users, and potentially audiences.

**Should AI be used?** Conditional yes. Generative summarization can create value when users genuinely face more review content than they can read. Organisation-facing summaries are a safer first use than public audience-facing summaries because professional users can inspect and correct them.

**Could deterministic software solve this better?** For small cohorts, yes. Theme counts, rating distributions, recommendation rates, selected excerpts, and templated statements are more reliable. AI should summarize only after those deterministic facts are available and visible.

**What data would be required?** Approved review text; review dates; controlled themes; rating and recommendation aggregates; cohort definition; source review identifiers; language; moderation state; summary correction feedback.

**Privacy considerations:** Use only content approved for the intended purpose. Public approval does not automatically grant unrestricted model-training rights. Avoid reproducing identifiable phrases from small cohorts. Apply minimum cohort sizes and prevent one organisation's content from informing another organisation's private summary without an approved aggregation policy.

**Risk of hallucination:** High. A model may exaggerate minority opinions, invent consensus, merge unrelated comments, or omit criticism. Every statement should be traceable to source evidence; unsupported-claim rate must be measured; factual metrics should be inserted deterministically rather than generated.

**Expected business value:** Speeds understanding, increases analytics adoption, and helps Encore deliver insight rather than another dashboard. Measure task completion time, unsupported-claim rate, user corrections, confidence, and whether summaries change a documented decision.

**Priority decision:** Near-term pilot after theme classification and deterministic insight are trusted.

### 3. Sentiment Interpretation

**Proposed use:** Help organisers understand audience sentiment and changing emotional response.

**Should AI be used?** Limited use. AI can classify nuance in free text, but “sentiment” should not be presented as an objective fact or substitute for ratings, recommendation choice, themes, and direct evidence.

**Could deterministic software solve this better?** Often. Existing rating and recommendation inputs are explicit audience signals. Lexical or taxonomy-based indicators may be adequate for early discovery. AI is helpful only for nuance such as mixed sentiment, aspect-level sentiment, idiom, and larger multilingual volumes.

**What data would be required?** Approved review text; explicit ratings; recommendation response; selected tags; language; human-labelled examples across genres, regions, age groups where lawful, and writing styles; correction feedback.

**Privacy considerations:** Free text can contain personal or sensitive information. Sentiment must not be used to infer mental state, protected characteristics, or vulnerability. Individual-level sentiment should not be exposed to organisations for targeting.

**Risk of hallucination:** Medium. Classification does not normally generate prose, but it can misread sarcasm, cultural language, mixed views, or criticism of venue versus production. Outputs should display uncertainty and source examples.

**Expected business value:** Adds nuance to review analysis and helps organisations identify experience areas requiring attention. Measure agreement with human judgment, aspect-level usefulness, error by cohort/language, and incremental value beyond explicit scores.

**Priority decision:** Near-term research alongside theme classification; do not make it a headline metric until validity is demonstrated.

### 4. Similar-Show Recommendations

**Proposed use:** Recommend relevant performances based on similarity to a show an audience member is viewing.

**Should AI be used?** Conditional. This is a legitimate recommender problem, but it does not require sophisticated AI initially. A useful product can be delivered from transparent catalogue similarity.

**Could deterministic software solve this better?** Initially, yes. Genre, venue, location, date, age suitability, accessibility, format, company, price band, and audience tags can generate explainable results. A learned ranking should be introduced only when sufficient interaction data exists and it outperforms rules.

**What data would be required?** Accurate catalogue metadata; performance availability; venue/location; accessibility; approved aggregate review themes; recommendation impressions; clicks, saves, and outbound ticket actions under an approved measurement purpose.

**Privacy considerations:** Non-personalized similarity can avoid personal data. Interaction measurement still requires transparency and retention rules. Paid placement must never be silently blended into relevance ranking.

**Risk of hallucination:** Low for retrieval and ranking. Generative descriptions could invent similarity reasons and should not be used; reasons should be generated from actual shared attributes.

**Expected business value:** Increases catalogue exploration, ticket referrals, and exposure for relevant lesser-known shows. Measure engagement against a rules baseline, discovery diversity, catalogue coverage, outbound ticket actions, and user dissatisfaction.

**Priority decision:** Near term as a deterministic product; AI ranking is a later optimization within the same horizon if justified by evidence.

### 5. Trend and Anomaly Detection

**Proposed use:** Identify changing audience behaviour or emerging patterns before they are obvious in manual reporting.

**Should AI be used?** Conditional yes, but statistical methods should precede complex models. The use is valuable when it reliably identifies actionable change earlier than existing reports.

**Could deterministic software solve this better?** Frequently. Rolling averages, period comparisons, confidence intervals, minimum sample rules, and statistical process-control alerts may be more transparent and stable. Machine learning is justified for multivariate or nonlinear patterns only after simpler baselines are exhausted.

**What data would be required?** Versioned time-series metrics; performance schedule; invitation and response coverage; moderation changes; audience/cohort definitions; known seasonality; intervention and outcome history; data-quality indicators.

**Privacy considerations:** Prefer aggregate cohorts. Small groups and sparse geographic or demographic changes can expose individuals. Detection should operate only on cohorts that pass disclosure and consent rules.

**Risk of hallucination:** Low for detection, but high if a generative layer explains causation. The product may report “this changed”; it must not claim “why it changed” without evidence.

**Expected business value:** Shortens time to notice meaningful change and supports timely investigation. Measure alert precision, missed-event rate, lead time, user action rate, and outcome improvement—not number of alerts.

**Priority decision:** Near-term deterministic/statistical pilot; machine learning only if complex patterns have demonstrated incremental value.

## Long Term

Long-term opportunities depend on durable audience identity and consent, representative longitudinal data, mature organisation intelligence, outcome feedback, and proven AI governance.

### 6. Personalized Discovery

**Proposed use:** Recommend performances an individual audience member is likely to enjoy.

**Should AI be used?** Conditional yes. AI may improve ranking once Encore has enough permissioned interaction and preference data. Personalization must remain optional and should complement, not replace, audience-controlled search and follows.

**Could deterministic software solve this better?** Initially, yes. Explicit genre, location, accessibility, date, venue/company follows, watchlist, and prior rating preferences can drive understandable recommendations. Collaborative or learned ranking is justified only when it improves value beyond those controls.

**What data would be required?** Explicit preferences; consented follows, watchlists, review history, attendance or purchase feedback where available; catalogue metadata; recommendation impressions and outcomes; negative feedback; availability; accessibility requirements; sufficient cross-catalogue coverage.

**Privacy considerations:** This creates longitudinal profiling. Users need a clear purpose, opt-in/opt-out, ability to inspect and reset preferences, and separation from marketing consent. Sensitive traits, demographic data, location history, and unsafe proxies should be excluded unless a specifically approved benefit requires them. Training and deletion propagation must be defined.

**Risk of hallucination:** Low in ranking, but inferred preferences can be wrong. Generated reasons can hallucinate and should use only verified attributes. Feedback loops may amplify popularity and narrow discovery.

**Expected business value:** Can increase repeat sessions, saves, discovery depth, ticket referrals, and audience retention. Measure incremental lift over explicit-preference rules plus diversity, novelty, smaller-organisation exposure, opt-out, hide/dislike signals, and trust.

**Priority decision:** Long term. Do not launch before the audience library, consent centre, measurement policy, and non-personalized recommendation baseline.

### 7. Action-Oriented Insight Suggestions

**Proposed use:** Turn audience intelligence into suggestions that answer, “What should I do next?”

**Should AI be used?** Limited and advisory. AI can make governed evidence easier to explore or draft options, but it should not make creative, employment, pricing, programming, safeguarding, or audience-targeting decisions.

**Could deterministic software solve this better?** Often. Decision playbooks, threshold-based guidance, role-specific checklists, and linked evidence provide consistent and auditable support. AI adds value when the combination of evidence and context is too varied for a small rule set.

**What data would be required?** Governed metrics; cohort and quality context; organisation goals; prior actions and outcomes; approved playbooks; operational constraints; evidence supporting each suggestion; user feedback on relevance.

**Privacy considerations:** Use aggregate organisation data wherever possible. Do not expose another tenant's confidential data or infer sensitive audience traits. User prompts and output logs require retention and access controls.

**Risk of hallucination:** High. A model may invent causes, metrics, constraints, or best practices. Suggestions must cite actual evidence, distinguish observation from hypothesis, display uncertainty, and require human judgment.

**Expected business value:** May reduce time to insight and increase the rate at which organisations act on Encore data. Measure accepted suggestions, user-reported usefulness, unsupported claims, decision time, and verified outcome improvement.

**Priority decision:** Long term, after deterministic playbooks reveal where flexible AI assistance has genuine incremental value.

### 8. Attendance or Demand Forecasting

**Proposed use:** Predict future attendance, demand, or audience response to support planning.

**Should AI be used?** Conditional yes for one narrowly defined decision. Forecasting is useful only when predictions are more accurate and actionable than simple seasonal or historical baselines.

**Could deterministic software solve this better?** A statistical baseline may solve it better for a long time. Moving averages, comparable-run analysis, booking curves, and seasonal models are more explainable and require less data. Complex machine learning is justified only by sustained out-of-sample improvement.

**What data would be required?** Historical performance and attendance outcomes; booking curves where contractually available; schedule; venue capacity; price and promotion context; genre/company history; cancellations; seasonality; coverage changes; intervention history. Review response alone is insufficient.

**Privacy considerations:** Prefer aggregate operational data. Individual profiling is unnecessary for most forecasts. Commercially sensitive organisation data must remain tenant-scoped, and cross-organisation training requires approved aggregation and disclosure rules.

**Risk of hallucination:** Low for numerical models, but generated explanations can invent causal stories. Forecasts must show uncertainty, assumptions, coverage, and known blind spots.

**Expected business value:** Could improve scheduling, staffing, inventory, and marketing timing. Measure forecast error against simple baselines and the financial or operational value of decisions changed by the forecast.

**Priority decision:** Long term pilot after sufficient longitudinal data and a willing design partner define a reversible use case.

### 9. Campaign Audience Suggestions

**Proposed use:** Suggest which audience segment may be interested in a performance or campaign.

**Should AI be used?** Generally defer. AI may eventually rank already-permitted segments, but it should not create opaque individual eligibility or infer sensitive interests.

**Could deterministic software solve this better?** Yes for the initial product. Explicit follows, watchlists, location radius, declared genres, attendance recency, consent, frequency, and suppression rules can create understandable segments.

**What data would be required?** Explicit audience preferences; consent and communication eligibility; campaign purpose; show metadata; prior delivery and response outcomes; negative feedback; suppression; fairness and frequency measures.

**Privacy considerations:** Very high. This combines profiling and direct marketing. Marketing consent must be separate from recommendation consent. Sensitive attributes and proxies should be prohibited. Organisations should receive eligible reach or aggregated segments rather than raw audience identities by default.

**Risk of hallucination:** Medium. Ranking does not generate facts, but a model can assign an unsupported interest or create a misleading segment explanation. The larger risks are discrimination, exclusion, and intrusive targeting.

**Expected business value:** Potentially increases campaign response and reduces wasted contact. Measure incremental attendance or ticket action, complaint and unsubscribe rates, contact frequency, fairness, and lift over explicit-rule segments.

**Priority decision:** Long term and advisory only, after governed campaign delivery and deterministic segmentation have proven both value and audience acceptance.

### 10. Generative Campaign Assistance

**Proposed use:** Draft campaign copy or variants using approved show facts and organisation guidance.

**Should AI be used?** Conditional yes. Drafting is a credible low-authority use when humans remain responsible for review and send approval.

**Could deterministic software solve this better?** Templates and approved content blocks solve routine messages more safely. AI is useful for tone or variant generation only if it saves meaningful time without increasing factual or brand risk.

**What data would be required?** Approved catalogue facts; ticket links; organisation brand and style guidance; channel constraints; legal disclosures; accessibility guidance; approved examples; user edits and rejection reasons.

**Privacy considerations:** Personal audience data is unnecessary for copy drafting and should not be included. Prompts may contain confidential organisation information and require tenant isolation, retention control, and vendor no-training terms.

**Risk of hallucination:** High. A model may invent dates, cast, prices, availability, accessibility, reviews, or claims. Factual fields should be inserted from authoritative sources and validated; human approval is mandatory.

**Expected business value:** May reduce campaign preparation time and increase useful experimentation. Measure time saved, edit distance, rejection rate, factual-error rate, accessibility quality, and incremental outcome of approved variants.

**Priority decision:** Long term optional capability after campaign governance; lower priority than audience consent, measurement, and relevant segment selection.

## Moonshot

Moonshots should remain research propositions. They must not influence near-term architecture or data collection unless a nearer-term approved capability independently justifies that investment.

### 11. Conversational Audience-Intelligence Copilot

**Proposed use:** Allow organisation users to ask natural-language questions, explore audience intelligence, and receive evidence-linked explanations or suggested next steps.

**Should AI be used?** Conditional, much later. Natural-language interaction is a genuine AI fit, but only after Encore has a mature, governed intelligence product. A conversational interface must not compensate for undefined metrics or poor information architecture.

**Could deterministic software solve this better?** Guided reports, saved questions, filters, explanations, and decision playbooks will solve common tasks more reliably. A copilot is justified only for the long tail of questions that cannot be represented effectively in structured workflows.

**What data would be required?** Governed metric catalogue; authorised tenant datasets; semantic definitions; role and permission context; approved knowledge base; source citations; query and correction feedback; evaluation questions with known answers.

**Privacy considerations:** The copilot must enforce the same tenant, cohort, field, purpose, and export controls as structured analytics. Prompts and responses can reveal confidential strategy and personal information. Retrieval and logs require strict scope and retention.

**Risk of hallucination:** High. It may invent metrics, causal explanations, comparisons, or actions. Responses must be generated from authorized queries, cite definitions and source periods, show quality limits, and refuse unsupported questions.

**Expected business value:** Could make sophisticated intelligence accessible to non-analysts and reduce analysis time. Measure answer correctness, task success, time saved, refusal quality, unsupported-claim rate, and incremental adoption beyond structured reports.

**Priority decision:** Moonshot; consider only after action-oriented intelligence is demonstrably valuable and trusted.

### 12. Autonomous AI-Powered Audience Engagement

**Proposed use:** Automatically choose audiences, generate communications, select timing/channel, and optimize engagement or ticket outcomes.

**Should AI be used?** No under the current principles. This would delegate high-impact commercial and audience decisions to a system combining profiling, persuasion, and communication. It conflicts with informed control and the requirement that AI empower rather than replace human responsibility.

**Could deterministic software solve this better?** Yes. Explicit consent, declared follows, approved segments, frequency limits, deterministic eligibility, templates, scheduled campaigns, and accountable human approval offer clear value with much stronger control.

**What data would be required?** Extensive audience profiles, communication eligibility, behavioural history, show context, delivery outcomes, conversion feedback, channel response, negative signals, and experimentation data. The breadth of data is itself a warning sign.

**Privacy considerations:** Very high. The capability risks opaque profiling, excessive contact, inferred vulnerability, proxy discrimination, purpose expansion, and difficulty exercising meaningful control. A person should never be contacted solely because an opaque model selected them.

**Risk of hallucination:** High for generated content and explanations. Even without textual hallucination, incorrect targeting and optimization are material harms.

**Expected business value:** Potential conversion lift is plausible, but the value is unlikely to outweigh trust, compliance, brand, and operating risk until every lower-risk engagement capability is mature. Measure against a human-approved campaign baseline; do not use engagement volume as proof of value.

**Priority decision:** Moonshot research only. Do not authorize autonomous targeting or sending. Any future reconsideration requires explicit strategic, legal, privacy, audience, and executive approval.

## Capabilities That Should Remain Deterministic

The following vision capabilities should not be treated as AI opportunities unless a later, specific problem demonstrates otherwise.

### Scores, Recommendation Rates, and Public Aggregates

These must remain documented calculations over approved reviews. AI would reduce reproducibility and trust without adding value.

### Geographic Reach and Consented Demographic Reporting

These require governed aggregation, cohort thresholds, clear definitions, and data-quality disclosure. AI is not required to calculate or present the initial product and must not infer missing demographic attributes.

### Production Benchmarking

Comparable cohort selection, normalization, and metric calculation should begin with explicit rules approved by product and domain experts. AI may later help users explore results, but should not silently decide which organisations are comparable.

### Marketing Attribution and Effectiveness

The initial product needs clear attribution rules, experiment design, and aggregate outcome measures. AI cannot repair incomplete outcome data or ambiguous causality.

### Consent, Communication Eligibility, and Suppression

These are policy decisions that must be deterministic, auditable, and enforced at action time. A model must never override a withdrawal, channel preference, suppression, or frequency limit.

### Review Publication Decisions

AI may eventually flag content for human review, but final moderation and appeals require accountable policy-led decisions. Publication should not depend solely on a model score.

### Identity Matching

Provider-to-audience account linking should be explicit and user-authorized. Probabilistic matching must not silently merge people or their histories.

### Search and Local Discovery Baseline

Text search, location radius, dates, genres, access needs, venue, and company filters should establish a transparent baseline before learned ranking.

## AI Product Release Gates

Every AI capability must pass the following gates independently.

### 1. Outcome Gate

- Named user, problem, decision, and owner.
- One primary outcome and explicit guardrail measures.
- Deterministic/manual baseline with current performance and cost.
- Minimum improvement required to justify AI.

### 2. Data Gate

- Approved purpose, lawful basis, consent position, and data rights.
- Provenance, representativeness, quality, cohort coverage, and known bias.
- Training, evaluation, inference, retention, correction, and deletion treatment.
- Vendor terms preventing unauthorized retention or model training.

### 3. Evaluation Gate

- Representative offline test set and documented human-evaluation protocol.
- Accuracy, groundedness, hallucination, bias, diversity, privacy leakage, robustness, accessibility, latency, and cost thresholds appropriate to the use.
- Comparison against the deterministic baseline.
- Failure cases and affected-user analysis.

### 4. Experience Gate

- Disclosure and explanation appropriate to impact.
- Evidence, confidence, and limitations visible where decisions are supported.
- Feedback, correction, opt-out, appeal, or human escalation as appropriate.
- Safe empty state and deterministic fallback.

### 5. Operational Gate

- Named model and product owners.
- Approved model/version and change policy.
- Production monitoring for quality, drift, cost, exposure, and incidents.
- Rollback and immediate suspension capability.
- No cross-tenant or unauthorized personal-data exposure.

### 6. Value Gate

- Controlled experiment or staged release demonstrates incremental user and business value.
- Guardrail measures show no unacceptable loss of trust, privacy, fairness, diversity, or user control.
- Full operating cost is justified by the measured benefit.
- A post-release review decides whether to expand, revise, or retire the capability.

## Recommended AI Portfolio Sequence

1. **Establish AI governance and measurement.** Approve the use-case register, risk tiers, evaluation method, vendor policy, incident process, and deterministic-baseline requirement.
2. **Build non-AI product foundations.** Deliver trusted review governance, audience identity and consent, governed metrics, search, explicit preferences, and descriptive organisation intelligence.
3. **Run one bounded offline pilot.** Evaluate review theme classification against manual tags and keyword rules using approved content.
4. **Pilot evidence-linked summarization.** Proceed only if review volume creates a demonstrated interpretation problem and source-grounded quality meets the release threshold.
5. **Deliver deterministic recommendations first.** Establish similar-show and explicit-preference baselines, then test learned ranking only for incremental value.
6. **Evaluate statistical trend detection.** Introduce more complex models only when transparent rules fail on a documented use case.
7. **Consider opt-in personalization.** Require adequate consented data, diversity controls, explanations, and sustained value.
8. **Pilot one narrow forecast or action assistant.** Keep it advisory, reversible, and evidence-linked.
9. **Keep campaign AI human-controlled.** Use AI only for suggestions or drafts after deterministic eligibility and campaign governance are mature.
10. **Do not pursue autonomous engagement.** Reconsider only through an explicit future strategic decision with independent evidence that lower-risk methods cannot deliver the required value.
