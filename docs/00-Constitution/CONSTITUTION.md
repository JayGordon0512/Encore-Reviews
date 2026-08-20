# The Encore Constitution

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 1.0

**Status:** Highest governing authority within the Encore repository

**Owner:** Founder & CEO

## Table of Contents

- [Constitutional Purpose](#constitutional-purpose)
- [Role of the Constitution](#role-of-the-constitution)
- [The Constitutional Layer](#the-constitutional-layer)
- [Constitutional Hierarchy](#constitutional-hierarchy)
- [What Encore Believes](#what-encore-believes)
- [What Must Never Change](#what-must-never-change)
- [Trust Promise](#trust-promise)
- [Conductor Principle](#conductor-principle)
- [Relationship with TicketPal](#relationship-with-ticketpal)
- [First Principles](#first-principles)
- [Decision Framework](#decision-framework)
- [Product Guardian](#product-guardian)
- [Engineering Guardian](#engineering-guardian)
- [Constitutional Governance](#constitutional-governance)
- [Related Governance Documents](#related-governance-documents)

## Constitutional Purpose

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

Purpose is Encore's highest substantive authority. Every future product, architecture, engineering and business decision should ultimately derive from it.

## Role of the Constitution

The Constitution explains:

- why Encore exists;
- what Encore believes;
- what must never change;
- how downstream product, architecture, ADR and engineering decisions derive their authority.

This Constitution is the highest governing document within the Encore repository. It incorporates the approved constitutional sources by reference and establishes how they should be interpreted together.

The Constitution does not describe current implementation and does not authorise application changes by itself.

## The Constitutional Layer

The following documents form the constitutional layer. They remain at their current paths to avoid unnecessary disruption:

1. [Core Purpose](../00-Vision/CORE-PURPOSE.md) — why Encore exists and the highest substantive authority.
2. [Theory of Change](../00-Vision/THEORY-OF-CHANGE.md) — how trusted experience becomes positive ecosystem change.
3. [The Encore Platform Manifesto](../00-Vision/The-Encore-Platform-Manifesto.md) — Encore's enduring beliefs, vision, mission and promise.
4. [Trust Promise and Authority Principle](../00-Vision/The-Encore-Platform-Manifesto.md#the-authority-principle) — the basis of trusted participation and intelligence.
5. [Conductor Principle](../00-Vision/CORE-PURPOSE.md#the-conductor-principle) — Encore brings specialist participants together without replacing them.
6. [First Principles](../00-Vision/The-Encore-Platform-Manifesto.md#our-principles) — enduring tests applied to platform decisions.
7. [Decision Framework](../00-Vision/Operating-Principles.md#decision-framework) — the governance path for significant initiatives.

The [Platform Strategy](../00-Vision/Platform-Strategy.md), [Product Blueprint](../00-Vision/Encore-Product-Blueprint.md), [Audience Journey](../00-Vision/Audience-Journey.md), and [Operating Principles](../00-Vision/Operating-Principles.md) translate the constitutional layer into strategic, product and operating direction without superseding it.

## Constitutional Hierarchy

```text
Purpose
   ↓
Principles
   ↓
Product
   ↓
Architecture
   ↓
Engineering
```

Engineering should never redefine Product.

Product should never redefine Purpose.

Purpose is the highest authority.

Architecture translates approved product direction into durable system boundaries. Engineering implements those boundaries. Neither implementation convenience nor an existing technical constraint may silently redefine a higher layer.

When two layers appear to conflict, work pauses at the affected decision boundary and returns to the appropriate governance review. The lower layer does not resolve the conflict by overriding the higher one.

## What Encore Believes

- Live entertainment is strongest when every participant benefits from trusted knowledge.
- Every live performance creates human knowledge that should not simply disappear.
- Trustworthy collective intelligence begins with verified experience.
- Open orchestration creates more value than replacement, exclusivity or platform lock-in.
- Audiences, organisers, venues, artists, ticketing providers and partners should each gain visible value from participation.
- Technology and AI serve people, product and purpose; they do not define them.
- Privacy, provenance and understandable control are conditions of trust.

## What Must Never Change

The following constitutional commitments must never be weakened by an implicit product, commercial or engineering decision:

- Encore exists to strengthen and orchestrate the live entertainment ecosystem.
- Trust is foundational, not a feature that may be traded for growth.
- Only verified attendance grants authority to make trusted audience contributions.
- Identity and authority remain separate concepts.
- Encore remains platform neutral.
- TicketPal's native advantage does not create provider exclusivity.
- Encore connects specialist ecosystem participants rather than replacing them.
- Collective intelligence originates from people; AI may amplify it but must not fabricate its source.
- Ecosystem benefit takes precedence over isolated product optimisation.
- Product defines what should be built; Engineering determines how to build it responsibly.

These commitments may be clarified through constitutional governance, but they must not be altered indirectly through a feature, ADR, integration, commercial agreement or implementation decision.

## Trust Promise

At Encore, anyone can discover.

Only people who were there can contribute.

Trusted reviews, recommendations, audience intelligence and AI-assisted insight derive their legitimacy from verified experience and traceable provenance.

## Conductor Principle

Encore doesn't replace the ecosystem.

Encore brings it together.

Orchestration means connecting audience journeys and specialist participants while allowing each participant to retain its role, value and appropriate independence.

## Relationship with TicketPal

TicketPal is Encore's flagship ticketing partner.

Encore remains platform neutral.

TicketPal represents the deepest integration within the ecosystem but is not the ecosystem itself.

Native advantage should come from integration quality, shared learning and participant value—not exclusivity or lock-in.

## First Principles

The approved [First Principles](../00-Vision/The-Encore-Platform-Manifesto.md#our-principles) derive from Purpose and must be interpreted through this Constitution.

Together they require every significant initiative to strengthen trust, orchestration or ecosystem value; create meaningful audience benefit or collective intelligence; respect privacy; preserve openness; and make complexity justify itself through measurable value.

## Decision Framework

Every future proposal should be evaluated against the following questions:

- Does this strengthen trust?
- Does this improve orchestration?
- Does this benefit the ecosystem?
- Does this improve the audience experience?
- Does this create meaningful collective intelligence?

If not, the proposal should be challenged before implementation.

A significant initiative must also complete the three stages in the [Operating Principles](../00-Vision/Operating-Principles.md):

1. Strategic Review.
2. Engineering Review.
3. Founder Approval.

## Product Guardian

Future Product Guardian reviews should validate:

- alignment with Core Purpose;
- alignment with the Constitution;
- alignment with First Principles;
- alignment with accepted ADRs;
- alignment with approved Product Specifications.

The Product Guardian protects long-term product integrity. Approval is not justified merely because a proposal is feasible, commercially attractive or compatible with current implementation.

Where a lower-level specification or ADR appears inconsistent with Purpose or the Constitution, the conflict must be raised rather than inherited into delivery.

## Engineering Guardian

Engineering reviews should additionally validate that:

- architecture supports constitutional principles;
- identity remains separate from authority;
- trust is preserved;
- platform neutrality is maintained;
- future integrations strengthen orchestration rather than replacing ecosystem participants.

Engineering should explain trade-offs, protect maintainability and security, and challenge any design whose technical shape weakens the intended product or constitutional outcome.

## Constitutional Governance

- The Constitution governs Product, Architecture, ADRs and Engineering.
- Constitutional sources should be read together and may not be selectively interpreted to bypass another approved principle.
- Downstream documents must reference the Constitution and state which constitutional outcome they advance where the relationship is material.
- A proposed constitutional amendment requires explicit Strategic Review, Engineering Review where technical consequences exist, and Founder Approval.
- Constitutional change must be deliberate, documented and discussed. It must never occur implicitly through code, schema, an ADR, a roadmap item or a commercial integration.
- Code and tests remain evidence of current implementation; they do not override Purpose.

## Related Governance Documents

- [Governance Summary](GOVERNANCE-SUMMARY.md)
- [Strategic Repository Review](REPOSITORY-REVIEW.md)
- [Future Document Structure Recommendations](DOCUMENT-STRUCTURE-RECOMMENDATIONS.md)
- [Vision and Product Boundaries](../00-Vision/README.md)
- [Engineering Handbook](../README.md)
