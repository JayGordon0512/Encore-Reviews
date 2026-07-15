# Encore Reviews Engineering Handbook

This directory is the authoritative engineering reference for Encore Reviews. It documents the system as implemented in the repository and separates current behavior from future direction.

Last verified against the codebase: 15 July 2026.

## Handbook map

| Area | Purpose |
| --- | --- |
| [00 — Vision](00-Vision/README.md) | Product purpose, principles, scope, and terminology |
| [Platform Charter](00-Vision/Encore-Platform-Charter.md) | Governing platform mandate, trust principles, and engineering boundaries |
| [01 — Architecture](01-Architecture/README.md) | Runtime design, components, data flow, and technical boundaries |
| [Security and tenancy](01-Architecture/security-and-tenancy.md) | Authentication, authorization, tenant isolation, and sensitive data handling |
| [02 — ADR](02-ADR/README.md) | Architecture decision records for decisions embodied in the code |
| [03 — API](03-API/README.md) | Current HTTP API contracts and integration behavior |
| [04 — Domain](04-Domain/README.md) | Domain entities, relationships, invariants, and lifecycles |
| [05 — Operations](05-Operations/README.md) | Local operation, deployment checks, migrations, testing, and troubleshooting |
| [Sprint 0 hardening report](05-Operations/sprint-0-hardening-report.md) | Dependency review, CI, migration evidence, authorization audit, and remaining risks |
| [06 — Roadmap](06-Roadmap/README.md) | Prioritised capability portfolio, delivery dependencies, and acceptance gates |

## Documentation policy

- Engineering changes and pull requests must follow [CONTRIBUTING.md](../CONTRIBUTING.md).
- Code and executable tests are the final source of truth when documentation and behavior disagree.
- A behavior belongs in the current-state sections only when it exists in the application.
- Proposed capabilities must remain in the roadmap until implementation, tests, and operational guidance exist.
- Architecture decisions that materially change system boundaries should add or supersede an ADR.
- API changes must update the API reference in the same change.
- Schema or lifecycle changes must update the domain reference in the same change.

## System at a glance

Encore Reviews is an independent review platform for live events. Organisations own shows and venues. Shows contain performances, and review invitations and reviews attach to a specific performance. TicketPal is the first implemented provider integration; it does not define the core domain language.

The application is a Laravel 12 monolith with Blade-rendered public and administration pages, JSON API endpoints, PostgreSQL persistence in Docker Compose, UUID identifiers for domain records, and Laravel session authentication for administrative users.
