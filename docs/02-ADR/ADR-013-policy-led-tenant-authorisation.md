# ADR-013: Policy-Led Tenant Authorisation

- Status: Accepted
- Date: 2026-07-15
- Scope: Session-authenticated administrative access

## Context

Organisation ownership checks were previously embedded in controllers. That made authorization inconsistent, difficult to review, and vulnerable to nested route binding where a resource could belong to a different organisation than the route parameter.

## Decision

Laravel Policies are the authoritative authorization mechanism for organisation administration, dashboard access, show assignment, and review moderation. Controllers invoke `Gate::authorize` and continue to use explicitly organisation-scoped queries for data retrieval. Policies validate relationships between all nested resources.

Public controllers and provider endpoints are outside user-policy authorization: public routes use their documented proof model, while provider routes use the integration authentication and event-ingestion controls.

## Consequences

- Authorization rules are centralized and independently testable.
- Query scoping remains required; policies do not automatically filter Eloquent results.
- PostgreSQL row-level security and a global tenant scope are not introduced.
- Every new administrative controller action must define and invoke a policy ability.

## Alternatives considered

### Manual controller comparisons

Rejected as the primary control because checks become duplicated and nested ownership is easy to omit.

### Global Eloquent tenant scopes

Not selected because super-administrator support access and unassigned shows require explicit exceptions that can become difficult to reason about.

### PostgreSQL row-level security

Potential defense in depth, but not selected without a database identity/session design that safely propagates the acting tenant.
