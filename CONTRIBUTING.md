# Contributing to Encore Reviews

This document defines the engineering standards for Encore Reviews. Every future pull request must conform to these requirements unless the pull request includes an approved architecture decision that changes them.

The [Engineering Handbook](docs/README.md), [Platform Charter](docs/00-Vision/Encore-Platform-Charter.md), and [Architecture Decision Records](docs/02-ADR/README.md) are normative project references. Code and executable tests remain the final source of truth when a discrepancy is found; the documentation must then be corrected in the same change.

## 1. Contribution principles

Every change must:

- preserve Encore's provider-neutral domain language;
- treat `Organisation` as the root ownership entity;
- make authentication, authorization, and organisation scope explicit;
- keep externally consumed API behavior intentional and documented;
- put multi-step business workflows in focused services;
- enforce critical integrity rules in PostgreSQL where practical;
- add tests proportional to risk;
- distinguish implemented capability from proposed future behavior;
- avoid unrelated refactoring in the same pull request.

Do not introduce functionality merely because supporting infrastructure or a reserved database field exists. Redis, queues, Mailpit, Meilisearch, trust-score fields, edit timestamps, and client hash fields do not represent implemented product behavior by themselves.

## 2. Development workflow

1. Branch from the current `main` branch.
2. Keep the branch focused on one coherent outcome.
3. Review the relevant handbook sections and ADRs before implementation.
4. Add or update tests with the implementation.
5. Update documentation in the same pull request.
6. Run the required quality gates.
7. Open a pull request with behavior, risk, migration, security, and verification details.

Do not commit environment files, secrets, local database files, dependency directories, or unrelated generated output.

## 3. Coding standards

### PHP and Laravel

- Follow Laravel 12 conventions and PSR-12 formatting as enforced by Laravel Pint.
- Use PHP types for parameters, return values, properties, and structured PHPDoc where they improve correctness.
- Prefer dependency injection over service location or manual container access.
- Prefer early validation and explicit domain failures over silent fallback behavior.
- Keep methods cohesive. Extract a service or focused private method when one method coordinates several domain operations.
- Use Eloquent relationships instead of repeating foreign-key joins when the relationship is part of the domain model.
- Prevent N+1 queries with deliberate eager loading on list and aggregate screens.
- Use transactions for workflows that must succeed or fail atomically.
- Use database constraints for durable identity and referential rules.
- Do not suppress exceptions without an explicit recovery action and test.
- Do not add abstractions solely for symmetry; abstractions must solve a demonstrated boundary or reuse problem.

Existing controller-led workflows are not a precedent for adding more complex controller logic. [ADR-005](docs/02-ADR/ADR-005-service-layer-architecture.md) establishes incremental movement toward services.

### Blade, CSS, and JavaScript

- Keep Blade views presentational. Database access and business decisions belong before rendering.
- Escape user-controlled values through Blade's escaped output syntax unless reviewed sanitization justifies raw HTML.
- Preserve accessible labels, keyboard behavior, semantic elements, and live-region behavior.
- Reuse existing Encore component classes and partials before creating parallel styles.
- Keep browser-side validation as usability support; server-side validation remains authoritative.
- Run the production Vite build for frontend changes.

## 4. Controller responsibilities

Controllers are HTTP delivery adapters.

Controllers should:

- receive route-bound dependencies and authenticated request context;
- invoke a Form Request or concise inline validation;
- call an application service for multi-step business behavior;
- translate service results and domain failures into HTTP responses, redirects, or views;
- select and pass already-prepared data to Blade views;
- remain explicit about status codes and response schemas.

Controllers must not:

- contain reusable provider synchronization algorithms;
- coordinate several aggregates without a service boundary;
- hide organisation-scoping rules inside presentation code;
- perform outbound network calls directly;
- dispatch ungoverned side effects before a transaction commits;
- become the only place where a domain invariant is enforced;
- return provider-specific models as unreviewed serialized Eloquent objects.

Small, single-aggregate CRUD operations may remain controller-led when a service would add no meaningful separation. The pull request must still preserve validation, authorization, and tenant scope.

## 5. Service responsibilities

Services implement application use cases and multi-step domain workflows.

Services should:

- have a focused, use-case-oriented name such as `PerformanceSyncService`;
- accept validated primitives, value objects, or future DTOs rather than an HTTP request;
- own transaction boundaries when several writes form one operation;
- resolve and enforce cross-entity invariants;
- return explicit results that a controller, command, or job can consume;
- be independent of response, redirect, session, and Blade concerns;
- expose predictable domain failures rather than constructing HTTP responses;
- remain idempotent where the use case may be delivered more than once.

Services must not become generic dumping grounds such as `CommonService`, `HelperService`, or `DatabaseService`. Split services by use case when responsibilities diverge.

Use `DB::transaction` deliberately. External side effects must not execute inside a retried transaction unless their duplicate behavior is explicitly safe.

## 6. Repository usage

Encore currently uses Eloquent directly and does not have a repository layer. Do not create one repository per model as a default pattern.

A repository may be introduced only when it provides a real boundary, for example:

- multiple persistence implementations are required;
- a complex query contract is reused across unrelated delivery mechanisms;
- provider or infrastructure data must be isolated from domain services;
- testing a volatile external persistence boundary requires substitution.

If introduced, a repository must:

- be named for the domain boundary or query responsibility, not merely the table;
- expose domain-relevant methods rather than mirror every Eloquent method;
- keep Eloquent and query-builder details behind the interface when substitution is the goal;
- define ownership and transaction behavior clearly;
- include tests for the contract;
- be justified in the pull request and, if broadly architectural, in an ADR.

Do not wrap Eloquent in pass-through interfaces that add no behavior or boundary.

## 7. Validation standards

All external input is untrusted.

- Validate every API, web form, command argument, provider payload, and queued payload before it reaches business logic.
- Use a Form Request for non-trivial, reusable, or externally consumed request contracts.
- Inline controller validation is acceptable for small web-only mutations.
- Use database-backed validation only as usability support; authorization and database constraints must still protect the write.
- Define maximum lengths for externally supplied strings.
- Validate enum-like values with explicit allow lists when the domain has a closed set.
- Normalize dates, email addresses, slugs, and provider identifiers deliberately.
- Preserve the distinction between an omitted optional field and an explicitly supplied null when update semantics require it.
- Return HTTP 422 for request or domain validation failures in JSON APIs.
- Never trust an `organisation_id` merely because it passed an `exists` rule; verify the caller may act on that organisation.

API validation changes are contract changes and require API documentation and tests.

## 8. Data Transfer Objects

DTOs are a future incremental pattern; Encore does not currently have a DTO layer. Do not claim DTO architecture is already implemented.

Introduce a DTO when one or more of these conditions apply:

- a validated payload crosses controllers, services, events, and jobs;
- array keys have become an error-prone implicit contract;
- input normalization should happen once at a layer boundary;
- a service result has multiple values with meaningful names;
- an external provider contract must be isolated from core domain terminology.

DTO standards when introduced:

- use immutable or `readonly` objects where possible;
- use typed constructor properties;
- name the DTO for its use case, such as `SyncPerformanceData`;
- keep HTTP request objects, Eloquent models, and service-container access out of DTOs;
- perform transport validation before construction;
- keep domain behavior in services or domain objects rather than DTO methods;
- provide explicit mapping at provider and delivery boundaries;
- test normalization and mapping behavior.

Do not replace small, stable method signatures with DTOs solely for ceremony.

## 9. Events

[ADR-007](docs/02-ADR/ADR-007-event-driven-processing.md) is Proposed. The current application has no domain event or listener architecture.

A pull request introducing the first business event must also:

- update ADR-007 to Accepted or supersede it;
- define event naming and payload compatibility;
- emit past-tense facts, such as `ReviewSubmitted`, rather than commands;
- use stable Encore identifiers in payloads;
- specify whether dispatch happens after database commit;
- define delivery guarantees and duplicate behavior;
- make listeners idempotent when more than one delivery is possible;
- define failure handling and observability;
- test dispatch timing, payload, listener behavior, and failure paths;
- update architecture, operations, and domain documentation.

Events are not a substitute for the authoritative state change. Do not use Laravel events as an undocumented call graph or introduce event sourcing without a separate accepted ADR.

## 10. Queues

[ADR-008](docs/02-ADR/ADR-008-queue-first-background-processing.md) is Proposed. Queue tables and configuration exist, but no business jobs are currently implemented.

A pull request introducing a queued workflow must:

- update ADR-008 to Accepted or supersede it;
- explain why the work is not request-critical;
- dispatch only after commit when the job depends on committed data;
- make the job safe for at-least-once execution;
- define timeout, retry count, backoff, and terminal failure behavior;
- prevent unbounded payload size and avoid serializing sensitive data unnecessarily;
- define worker deployment and supervision requirements;
- provide failed-job diagnosis and recovery instructions;
- add monitoring expectations for failures, backlog, latency, and worker health;
- test successful execution, retries, duplicate execution, and permanent failure.

Do not use fire-and-forget shell processes from HTTP requests. Do not move authoritative validation or an immediate caller-required state change into a queue merely to shorten code.

## 11. Naming conventions

### Domain language

- Use British spelling: `Organisation`, `organisation_id`, and `organisations`.
- Do not reintroduce `ClientAccount`, `client_account_id`, or provider-defined tenant terminology.
- Use `Show` for the production/event and `Performance` for a scheduled occurrence.
- Use `ReviewInvitation` for performance-level submission eligibility.
- Keep provider names at integration boundaries, such as `TicketPal` namespaces and `/api/ticketpal` routes.

### PHP symbols

| Type | Convention | Example |
| --- | --- | --- |
| Model | Singular PascalCase | `Organisation`, `Performance` |
| Controller | Use-case or resource + `Controller` | `PerformanceUpsertController` |
| Service | Use-case + `Service` | `PerformanceSyncService` |
| Form Request | Action/resource + `Request` | `UpsertPerformanceRequest` |
| Event | Past-tense domain fact | `ReviewSubmitted` |
| Listener | Action performed | `RecordReviewAnalytics` |
| Job | Imperative work name | `SendReviewInvitation` |
| DTO | Use-case data name | `SyncPerformanceData` |
| Migration | Timestamp + descriptive snake case | `..._enforce_performance_sync_keys.php` |
| Test | Behavior-oriented class/method | `PerformanceSyncTest` |

### Routes and API fields

- Use plural resource nouns and explicit actions where an upsert is not standard REST CRUD.
- Name internal routes by domain terminology even when a customer-facing URL uses friendlier wording.
- Use snake_case JSON fields.
- Keep response schemas explicit; do not expose accidental model fields.
- Provider IDs must retain their provider qualifier, such as `provider_performance_id`.

### Database

- Use plural snake_case table names.
- Use singular snake_case foreign keys.
- Name booleans with `is_`, `has_`, `can_`, or a clear predicate.
- Store Encore identity separately from provider identity.
- Prefer explicit composite uniqueness for provider-scoped keys.

## 12. Migration policy

PostgreSQL is the authoritative deployed database under [ADR-003](docs/02-ADR/ADR-003-postgresql-selected-for-encore.md).

- Never modify a migration that has run in any shared environment.
- Correct deployed schema with a new forward migration.
- A migration confirmed pending everywhere may be refactored before merge, but the pull request must state that verification.
- Every migration must have a meaningful `down()` method unless rollback is genuinely unsafe; document any exception.
- Add foreign keys, uniqueness constraints, and indexes that enforce or support the domain rule.
- Specify cascade, restrict, or null-on-delete behavior deliberately.
- Assess existing data before adding a unique or non-null constraint.
- Separate large data backfills from long-running blocking schema changes when operational risk requires it.
- Avoid destructive column/table operations without a backup, rollout, and rollback plan.
- Treat enum changes and column type changes as compatibility-sensitive PostgreSQL operations.
- Run migrations against PostgreSQL in addition to SQLite-based automated tests.
- Use UTC for synchronized timestamps and document timezone assumptions.

Migration pull requests must include:

- schema purpose and affected data;
- migration and rollback commands;
- lock or downtime assessment;
- backfill or compatibility plan where applicable;
- evidence from `migrate:status` or `migrate --pretend`;
- tests for the resulting application behavior.

## 13. Testing standards

Every behavior change requires tests at the lowest useful level and at its public boundary.

### Feature tests

Add feature tests for:

- routes, middleware, validation, status codes, and response schemas;
- authentication and authorization;
- organisation isolation and cross-tenant denial;
- database persistence and public visibility;
- provider idempotency and retry behavior;
- review invitation consumption and moderation state;
- regression cases for the defect being fixed.

### Unit and service tests

Use focused unit or service tests when behavior can be tested without the full HTTP stack. Test transaction-sensitive service behavior through the database when necessary.

### Test quality

- Use `RefreshDatabase` for isolated database feature tests.
- Do not depend on test execution order or pre-existing local data.
- Use factories or explicit minimal records with meaningful names.
- Assert both the response and authoritative database state for mutations.
- Include failure and boundary cases, not only the happy path.
- Assert that unauthorized or cross-organisation requests do not mutate data.
- For idempotent operations, repeat the request and assert record counts and stable IDs.
- Keep time-dependent tests deterministic where practical.
- Do not weaken assertions merely to make a test pass.

The default PHPUnit configuration uses in-memory SQLite. Any migration, locking, JSON, indexing, or PostgreSQL-specific behavior must also receive a PostgreSQL preflight or integration check.

## 14. Security requirements

Every pull request must evaluate security impact.

### Authentication and authorization

- Put routes behind the correct middleware.
- Enforce active-user and active-organisation state for customer administration.
- Check role and resource ownership server-side.
- Treat route model binding and `exists` validation as lookup mechanisms, not authorization.
- Preserve read-only support access unless a separately approved workflow authorizes mutation.

### Tenant isolation

- Scope organisation data explicitly in every query and mutation.
- Follow indirect ownership through `review → performance → show → organisation` where applicable.
- Add a cross-organisation denial test for every new tenant-sensitive operation.
- Never accept ownership reassignment implicitly from an untrusted payload.

### Sensitive data and secrets

- Never commit or log passwords, shared secrets, invitation tokens, raw emails, session identifiers, or provider credentials.
- Store secrets in environment or managed secret configuration.
- Use constant-time comparison for shared secrets and sensitive hashes.
- Preserve token and email hashing behavior unless an approved security design changes it.
- Remember that plain SHA-256 email hashes are pseudonymous, not anonymous.
- Return raw invitation tokens only through the intentional creation contract.

### Input and output safety

- Validate and bound all external input.
- Escape user content in Blade.
- Avoid mass assignment from unfiltered request arrays.
- Return only intentional API fields.
- Do not reveal whether cross-tenant resources exist; use the established 404 behavior where appropriate.
- Review URL ingestion, file paths, and future uploads for SSRF, traversal, content-type, and storage risks.
- Consider endpoint-specific rate limiting for publicly reachable or credentialed APIs.

Security-sensitive changes must document threat, control, residual risk, and tests in the pull request.

## 15. Documentation requirements

Documentation is part of the implementation, not follow-up work.

Update the relevant documents when a pull request changes:

| Change | Required documentation |
| --- | --- |
| Product purpose or platform boundary | Platform Charter and Vision |
| Runtime component or request flow | Architecture |
| Authentication, authorization, tenancy, or sensitive data | Security and tenancy |
| Durable architecture decision | New or superseding ADR |
| API field, validation, response, error, or auth | API reference |
| Entity, relationship, invariant, or lifecycle | Domain reference |
| Setup, migration, worker, deployment, recovery, or troubleshooting | Operations |
| Future capability or delivery status | Roadmap |

Rules:

- Do not document planned behavior as current.
- Mark future architecture as Proposed until implemented and accepted.
- Keep examples consistent with actual field names and status codes.
- Update relative links when files move.
- Retain superseded ADRs for history rather than rewriting accepted decisions invisibly.
- Record material breaking changes and migration requirements in the pull request.

## 16. Required quality gates

Run the applicable checks before requesting review:

```bash
php artisan test
./vendor/bin/pint --test
npm run build
git diff --check
```

For PostgreSQL schema changes, also run through Sail:

```bash
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail artisan migrate --pretend
```

If a check cannot run, explain why, identify the unverified risk, and provide the closest available evidence. A missing local tool is not permission to claim the check passed.

## 17. Pull request requirements

Every pull request description must include:

- the problem and intended outcome;
- implemented behavior and explicit non-goals;
- files or architectural areas affected;
- authentication, authorization, and organisation-scope impact;
- API compatibility impact;
- migration, deployment, and rollback requirements;
- test and quality-gate results;
- documentation updated;
- known risks or follow-up work.

### Author checklist

- [ ] The change follows the Platform Charter and accepted ADRs.
- [ ] Controllers contain only delivery and simple orchestration responsibilities.
- [ ] Multi-step business logic is in a focused service.
- [ ] Validation covers all external input and length boundaries.
- [ ] Organisation scope and authorization are explicit and tested.
- [ ] Critical integrity is protected by appropriate database constraints.
- [ ] API contracts and compatibility have been reviewed.
- [ ] Events, queues, repositories, or DTOs follow their introduction rules.
- [ ] Migrations are forward-safe and PostgreSQL-aware.
- [ ] Success, failure, regression, and tenant-isolation tests are present.
- [ ] Security impact and sensitive-data handling were reviewed.
- [ ] Handbook and API/domain/operations documentation are current.
- [ ] Tests, Pint, build, and diff checks pass.
- [ ] The pull request contains no unrelated changes or secrets.

Reviewers should block a pull request that violates these standards without an explicit, approved change to the governing documentation.
