# Encore Reviews Engineering Handbook

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

This directory is the authoritative engineering reference for Encore Reviews. It documents the system as implemented in the repository and separates current behavior from future direction.

**Constitutional foundation:** [The Encore Constitution](00-Constitution/CONSTITUTION.md) is the highest governing authority within the repository. [Encore Core Purpose](00-Vision/CORE-PURPOSE.md) is the highest substantive authority. The [Encore Theory of Change](00-Vision/THEORY-OF-CHANGE.md) explains how trusted experience and collective intelligence create ecosystem value. The [Manifesto](00-Vision/The-Encore-Platform-Manifesto.md), [Platform Strategy](00-Vision/Platform-Strategy.md), [Product Blueprint](00-Vision/Encore-Product-Blueprint.md), and [Audience Journey](00-Vision/Audience-Journey.md) translate Purpose into strategic and product direction. The [Operating Principles](00-Vision/Operating-Principles.md) define decision and Guardian governance.

Last verified against the codebase: 16 July 2026.

## Handbook map

| Area | Purpose |
| --- | --- |
| [The Encore Constitution](00-Constitution/CONSTITUTION.md) | Highest governing authority for every business, product, architecture, ADR and engineering decision |
| [Constitutional Governance Summary](00-Constitution/GOVERNANCE-SUMMARY.md) | Constitutional hierarchy, Guardian model, terminology decision and implementation boundary |
| [Constitutional Repository Review](00-Constitution/REPOSITORY-REVIEW.md) | Cross-document alignment, inconsistencies and recommended amendments |
| [Document Structure Recommendations](00-Constitution/DOCUMENT-STRUCTURE-RECOMMENDATIONS.md) | Non-disruptive proposal for future Constitution, Product, Architecture, ADR and Engineering layers |
| [Encore Core Purpose](00-Vision/CORE-PURPOSE.md) | Constitutional Purpose and highest substantive authority for the platform |
| [Encore Theory of Change](00-Vision/THEORY-OF-CHANGE.md) | Foundational model explaining why Encore deserves to exist and how the ecosystem benefits from trusted experience and collective intelligence |
| [The Encore Platform Manifesto](00-Vision/The-Encore-Platform-Manifesto.md) | Enduring vision, mission, beliefs, and promise derived from the Core Purpose |
| [Platform Strategy](00-Vision/Platform-Strategy.md) | Open-platform position, TicketPal native advantage, provider ecosystem, and commercial strategy |
| [Encore Product Blueprint](00-Vision/Encore-Product-Blueprint.md) | Authoritative product principles, participant value, capability model, and product boundaries |
| [Audience Journey](00-Vision/Audience-Journey.md) | Authoritative audience path from open discovery through verified contribution and optional membership |
| [Operating Principles](00-Vision/Operating-Principles.md) | Leadership model, decision framework, product guardrails, and operating principles for every significant initiative |
| [Core Purpose Alignment Review](00-Vision/Core-Purpose-Alignment-Review.md) | Product Guardian confirmation, strategic alignment findings, and separately recorded recommendations |
| [Theory of Change Governance Summary](00-Vision/Theory-of-Change-Governance-Summary.md) | Product Guardian alignment outcome, terminology decision, and recommendations for discussion |
| [00 — Vision](00-Vision/README.md) | Strategic hierarchy, product principles, scope, and terminology |
| [Platform Charter](00-Vision/Encore-Platform-Charter.md) | Governing platform mandate, trust principles, and engineering boundaries |
| [01 — Architecture](01-Architecture/README.md) | Runtime design, components, data flow, and technical boundaries |
| [ADR-000 — Founding Principles](01-Architecture/ADR-000-Founding-Principles.md) | Founding decision that every feature must strengthen the ecosystem, not simply an individual product |
| [ADR-015 — Authority Through Verification](02-ADR/ADR-015-authority-through-verification.md) | Identity grants access; explicit verification grants trusted contribution authority |
| [Authority Principle Engineering Assessment](01-Architecture/Authority-Principle-Engineering-Assessment.md) | Current identity/authority separation, critical gaps, and implementation recommendations |
| [Provider-Agnostic Architecture Assessment](01-Architecture/Provider-Agnostic-Architecture-Assessment.md) | Current provider assumptions, suggested interface boundary, and staged provider roadmap |
| [Security and tenancy](01-Architecture/security-and-tenancy.md) | Authentication, authorization, tenant isolation, and sensitive data handling |
| [02 — ADR](02-ADR/README.md) | Architecture decision records for decisions embodied in the code |
| [Decision Register](Decision-Register.md) | Consolidated status, ownership, and notes for every architectural decision |
| [03 — API](03-API/README.md) | Current HTTP API contracts and integration behavior |
| [Provider API Specification v2](03-API/Provider-API-Specification-v2.md) | Provider-neutral contract authority, current/target boundary, and approval gates |
| [Provider Interface Control Document](03-API/Interface-Control-Document.md) | Formal responsibilities, ownership, operation, and change control |
| [04 — Domain](04-Domain/README.md) | Domain entities, relationships, invariants, and lifecycles |
| [05 — Operations](05-Operations/README.md) | Local operation, deployment checks, migrations, testing, and troubleshooting |
| [Provider integration test plan](05-Operations/End-to-End-Integration-Test-Plan.md) | Joint authentication, replay, failure, audit, and correlation verification |
| [Sprint 0 hardening report](05-Operations/sprint-0-hardening-report.md) | Dependency review, CI, migration evidence, authorization audit, and remaining risks |
| [06 — Roadmap](06-Roadmap/README.md) | Prioritised capability portfolio, delivery dependencies, and acceptance gates |
| [TicketPal v2 migration](06-Roadmap/TicketPal-Provider-v2-Migration.md) | Proposed migration phases, risks, rollback, and success evidence |
| [Sprint 1 Enterprise Integration](06-Roadmap/Sprint-1-Enterprise-Integration.md) | Planning-only capability placeholders and readiness gates |
| [Engineering Governance Closure](06-Roadmap/Engineering-Governance-Closure.md) | Deferred governance backlog; product design and Showcase Release currently take priority |
| [07 — Releases](07-Releases/README.md) | Permanent release records, breaking changes, migration notes, and deployment evidence |
| [v0.3.0 Enterprise Foundation](07-Releases/v0.3.0-Enterprise-Foundation.md) | Programme closure and official architectural baseline |

## Platform Baseline

**v0.3.0 Enterprise Foundation is the official architectural baseline for Encore Reviews.** All future capabilities must conform to the approved architecture, domain language, security controls, tenancy boundaries, integration governance, engineering standards, and documentation hierarchy established by this release.

No architectural change affecting any of the following may be implemented implicitly:

- domain model;
- security;
- integrations;
- deployment;
- tenancy;
- public APIs.

Before implementation, every significant initiative must complete the three stages defined by the [Operating Principles](00-Vision/Operating-Principles.md):

1. Strategic Review.
2. Engineering Review, including an updated Provider API Specification where an external provider contract is affected and an approved new or superseding Architecture Decision Record where architecture changes.
3. Founder Approval.

The applicable domain, security, operations, roadmap, release, and API documentation must be updated in the same change. Proposed decisions and capability placeholders do not authorize implementation.

## Documentation policy

- Engineering changes and pull requests must follow [CONTRIBUTING.md](../CONTRIBUTING.md).
- The Constitution is the highest governing authority and Purpose is the highest substantive authority; code and executable tests are the final source of truth for currently implemented behaviour. An inconsistency must be raised and resolved through the applicable governance process rather than allowing implementation to redefine Product or Purpose.
- A behavior belongs in the current-state sections only when it exists in the application.
- Proposed capabilities must remain in the roadmap until implementation, tests, and operational guidance exist.
- Architecture decisions that materially change system boundaries should add or supersede an ADR.
- API changes must update the API reference in the same change.
- Schema or lifecycle changes must update the domain reference in the same change.
- Product, architecture, and roadmap proposals should demonstrate alignment with the Core Purpose and Theory of Change and explain how they advance the Manifesto's mission and principles where applicable.
- Significant initiatives must follow the leadership responsibilities, decision framework, and Product Guardian questions in the [Operating Principles](00-Vision/Operating-Principles.md).

### Provider integration governance

The Provider API Specification is the single source of truth for all external provider integrations. No changes to the integration contract may be implemented in code until the Provider API Specification has been updated and approved. Any architectural change affecting provider integrations must also be recorded in an Architecture Decision Record (ADR) before implementation.

Document status remains binding: proposed operations are not executable contracts and must not be represented as implemented until their approval, code, tests, migration guidance, and operational evidence are complete.

## System at a glance

Encore Reviews is an independent review platform for live events. Organisations own shows and venues. Shows contain performances, and review invitations and reviews attach to a specific performance. TicketPal is the first implemented provider integration; it does not define the core domain language.

The application is a Laravel 12 monolith with Blade-rendered public and administration pages, JSON API endpoints, PostgreSQL persistence in Docker Compose, UUID identifiers for domain records, and Laravel session authentication for administrative users.
