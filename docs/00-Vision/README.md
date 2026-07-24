# Vision and Product Boundaries

The strategic foundation of the Encore Platform is:

1. [The Encore Platform Manifesto](The-Encore-Platform-Manifesto.md) — enduring purpose, mission, beliefs, and promise.
2. [Platform Strategy](Platform-Strategy.md) — independent open-platform position, provider ecosystem, TicketPal native advantage, and commercial strategy.
3. [Encore Product Blueprint](Encore-Product-Blueprint.md) — authoritative product principles, participants, capabilities, boundaries, and outcomes.
4. [Audience Journey](Audience-Journey.md) — authoritative path from open discovery through verified contribution and optional membership.
5. [Operating Principles](Operating-Principles.md) — leadership responsibilities, decision framework, and product/engineering guardrails.
6. [Encore Reviews Platform Charter](Encore-Platform-Charter.md) — formal platform mandate and governing engineering boundaries.

[ADR-000](../01-Architecture/ADR-000-Founding-Principles.md) records the founding ecosystem decision. [ADR-015](../02-ADR/ADR-015-authority-through-verification.md) records the permanent distinction between identity and contribution authority.

The latest governance assessments are the [Authority Principle Product Guardian Review](Authority-Principle-Product-Guardian-Review.md), the separate [documentation inconsistencies](Authority-Principle-Documentation-Inconsistencies.md), the [Authority Principle Engineering Architecture Assessment](../01-Architecture/Authority-Principle-Engineering-Assessment.md), and the [Provider-Agnostic Architecture Assessment](../01-Architecture/Provider-Agnostic-Architecture-Assessment.md).

## Purpose

Encore Reviews exists to collect and publish trustworthy audience feedback for live performance. It connects reviews to attendance evidence at the performance level while keeping the review platform independent from any one ticketing provider.

## Core product model

The root ownership entity is an **Organisation**. An organisation may represent a theatre company, venue, festival, dance school, college, touring company, comedy promoter, music organisation, or another live-event operator.

TicketPal customers are organisations when their data is synchronized into Encore. TicketPal itself is an external provider integration.

The implemented ownership path is:

```text
Organisation
├── Users
├── Shows
│   └── Performances
│       ├── Review invitations
│       └── Reviews
└── Venues
```

## Product principles

### Independent domain language

Core entities use Encore terminology. Provider identifiers are retained as integration metadata and idempotency keys; they do not replace Encore entities.

### Verified at performance level

A review is created from a single-use invitation tied to a performance. This preserves the relationship between the feedback and a specific occurrence of a show.

### Public trust through moderation

New reviews enter a pending state. Only approved reviews contribute to public review lists, average scores, review counts, and recommendation rates.

### Organisation isolation

Customer administrators see and moderate data belonging to their organisation. Encore super administrators manage organisations and can inspect an organisation dashboard in read-only support mode.

### Provider synchronization must be repeatable

Provider show and performance synchronization is designed as upsert behavior. Repeating the same provider payload updates the same Encore record instead of creating duplicates.

## Current product scope

Implemented capabilities include:

- public homepage, show directory, show detail pages, scores, and approved review display;
- invitation-based public review submission;
- TicketPal-authenticated show, performance, and invitation endpoints;
- organisation-scoped customer dashboard and review moderation;
- Encore super-administration for organisations, users, and show assignment;
- read-only support views for Encore administrators;
- local Docker Compose services and automated feature tests.

## Explicitly outside the current implementation

The following are not current capabilities:

- embeddable review widgets;
- organisation analytics products or exports;
- a generic provider adapter framework;
- integrations with providers other than TicketPal;
- automated invitation email delivery;
- public show search or filtering;
- self-service registration, password reset, or organisation onboarding;
- background synchronization orchestration;
- an audit log for administrative changes.

Future direction is tracked separately in [the roadmap](../06-Roadmap/README.md).
