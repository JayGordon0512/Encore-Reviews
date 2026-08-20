# ADR-004: API-First Architecture

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Accepted
- Date: 2026-07-15
- Scope: Integration and application delivery boundaries

## Context

Encore must receive structured data from external providers and support audience review submission independently from server-rendered administration pages. Provider contracts require stable validation, authentication, error behavior, and idempotency.

The current product is not a headless application: public and administrative experiences use Blade. API-first therefore describes how interoperable capabilities are defined, not a requirement that every screen call a public API.

## Decision

Treat HTTP API contracts as first-class architecture for provider integrations and externally consumable workflows.

New integration capabilities must define request validation, authentication, authorization, idempotency, response schemas, and failure behavior before implementation is considered complete. HTTP delivery should delegate multi-step business behavior to reusable application services rather than embed it permanently in controllers.

Blade web routes may remain the appropriate delivery mechanism for public and administrative user interfaces. Internal web-only mutations do not need artificial public API endpoints unless another consumer requires them.

## Consequences

- TicketPal show, performance, and invitation operations have explicit JSON endpoints.
- Audience review submission has an explicit API contract used by the public form.
- API documentation must change alongside contract changes.
- The current API is unversioned, so breaking changes require coordinated consumers or a future versioning decision.
- Controllers must not become the only reusable location for complex business rules.
- API-first does not imply an SPA, microservices, or public exposure of administrative operations.

## Alternatives considered

### Server-rendered forms and direct controller logic only

Rejected for provider workflows because external systems require stable machine-readable contracts.

### Fully headless API with a separate frontend

Not selected. The current Blade application is simpler and meets the implemented public and administrative needs.

### Direct provider access to Encore's database

Rejected because it would bypass validation, authorization, transactions, domain rules, and contract governance.
