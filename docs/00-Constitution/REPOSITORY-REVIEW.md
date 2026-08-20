# Constitutional Repository Review

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 1.0

**Status:** Product Guardian and Engineering Guardian review

**Review date:** 24 July 2026

## Purpose

This review evaluates the existing strategic foundation against [The Encore Constitution](CONSTITUTION.md). It records consistency, ambiguities and recommended amendments without authorising implementation changes.

## Review Outcome

The strategic direction is constitutionally coherent. Trust, verified authority, platform neutrality, ecosystem value, audience benefit and human-origin collective intelligence reinforce one another.

No reviewed document directly rejects the Constitution. Several documents predate the constitutional hierarchy and need future terminology or scope clarification so that they cannot be interpreted as competing sources of purpose.

## Document Review

| Document | Alignment | Finding |
| --- | --- | --- |
| [Core Purpose](../00-Vision/CORE-PURPOSE.md) | Aligned with amendment | Its substance defines Purpose, but its status previously described it as the repository's highest governing document. The Constitution now occupies that governance role while Purpose remains the highest substantive authority. |
| [Theory of Change](../00-Vision/THEORY-OF-CHANGE.md) | Aligned | Explains how verified human experience becomes collective intelligence and ecosystem change. Collective/shared terminology requires constitutional interpretation. |
| [Manifesto](../00-Vision/The-Encore-Platform-Manifesto.md) | Aligned with recommendation | Trust, discovery, intelligence and ecosystem value align. “Intelligence layer” should eventually be framed as part of the broader orchestration role. |
| [Platform Strategy](../00-Vision/Platform-Strategy.md) | Aligned | Provider neutrality, flagship TicketPal integration, independent value and graceful degradation support the Conductor Principle. |
| [Product Blueprint](../00-Vision/Encore-Product-Blueprint.md) | Aligned with recommendation | Trusted discovery, intelligence and engagement support Purpose. A future orchestration capability map would strengthen traceability. |
| [Audience Journey](../00-Vision/Audience-Journey.md) | Aligned | Connects discovery through rediscovery while preserving verified contribution and optional membership. |
| [Operating Principles](../00-Vision/Operating-Principles.md) | Aligned with amendment | The existing Product Guardian model aligns and has been extended with constitutional Product and Engineering Guardian checks. |
| [ADR-015](../02-ADR/ADR-015-authority-through-verification.md) | Aligned | Identity/authority separation and verified provenance implement the Trust Promise at the architecture-policy level. |

## Identified Inconsistencies and Ambiguities

### 1. Constitutional authority versus purpose authority

Earlier documents describe the Core Purpose as the highest-level source of truth. The new model distinguishes the Constitution as the highest governing authority and Purpose as the highest substantive authority. Downstream documents should use this distinction consistently.

### 2. Collective intelligence versus shared intelligence

The Constitution uses the approved term **collective intelligence** in the Purpose statement. Earlier sources used **shared intelligence**. These can coexist only with a clear definition: collective intelligence is the knowledge created from verified human experience; shared intelligence is its appropriate distribution and application.

### 3. Platform orchestration versus intelligence-layer positioning

The Manifesto and Product Blueprint primarily position Encore as an Audience Intelligence Platform. This remains valid, but incomplete if interpreted without the constitutional orchestration role.

### 4. Current Platform Charter scope

The [Encore Reviews Platform Charter](../00-Vision/Encore-Platform-Charter.md) describes the narrower implemented review platform. Its authority and name could be mistaken for a complete definition of Encore. It should be explicitly scoped as a current-product engineering charter or revised later into a wider platform charter.

### 5. Product Specifications are not yet a distinct governed layer

The Product Blueprint and Audience Journey provide authoritative product direction, but the repository has no distinct Product Specifications area or consistent specification status model. Product Guardian review against “Product Specifications” therefore needs a documented definition before becoming mechanically enforceable.

### 6. ADR path and numbering do not match the proposed constitutional structure

ADRs currently live across `docs/01-Architecture/` and `docs/02-ADR/`. The proposed future structure places them under `docs/03-ADR/`. Moving them now would create link churn and should be treated as a planned documentation migration.

### 7. Engineering governance is distributed

Engineering policy currently spans the handbook, `CONTRIBUTING.md`, architecture documents, operational guides and roadmap governance. This is workable but makes the boundary between constitutional constraints, accepted architecture and delivery practice less obvious.

## Recommendations

1. Use the Constitution as the first reference in every future strategic, Product Guardian and Engineering Guardian review.
2. Retain Purpose as the highest substantive authority and remove future wording that treats a lower-level document as able to redefine it.
3. Use **collective intelligence** in constitutional purpose statements; reserve **shared intelligence** for distribution or application.
4. Add constitutional traceability to new Product Specifications, ADRs and significant roadmap initiatives.
5. Clarify the Platform Charter's scope before the next architecture-baseline revision.
6. Define what qualifies as an approved Product Specification, including owner, status, version, constitutional outcomes and acceptance evidence.
7. Plan any directory migration separately with link mapping, redirects where supported and a single controlled documentation change.

## Guardian Conclusion

The reviewed strategic documents support the Constitution. The identified issues are hierarchy, terminology and document-scope ambiguities rather than conflicting strategic intent.

No application change is authorised or required by this review.
