# Sprint 0 Enterprise Platform Hardening Report

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Date: 15 July 2026
- Branch: `feature/enterprise-foundation`
- Scope: engineering-platform hardening only
- Overall recommendation: Further work required before production; ready for engineering review and merge after TicketPal contract coordination

## Outcome

Sprint 0 resolved the existing formatter failures, removed all known Composer and npm advisories, added PostgreSQL continuous integration, introduced signed and replay-safe TicketPal event ingestion, made implemented administrative activity auditable, and moved session-authenticated tenant authorization to Laravel Policies with explicit query scoping.

No new customer product capability was introduced. ADR-007 and ADR-008 remain Proposed: provider event ingestion is synchronous delivery control, not domain-event publication or queue processing.

## Dependency security review

The audit used the lockfiles and the package-manager advisory databases. Encore was conservatively treated as potentially affected whenever a vulnerable package was present in a reachable production dependency path; no exploit claim was inferred without deployment evidence. Development-only tooling cannot affect a correctly built production install but remains relevant to developer and CI security.

### Composer advisories remediated

| Package | Previous | Minimum/current fixed | Severity | Encore exposure | Upgrade and breaking risk |
| --- | --- | --- | --- | --- | --- |
| `laravel/framework` | 12.48.1 | 12.61.1 | High, Medium | Potentially affected runtime HTTP framework | Same-major upgrade; medium regression surface, covered by full suite |
| `guzzlehttp/guzzle` | 7.10.0 | 7.12.1 | Medium | Potential runtime HTTP client path | Same-major patch/minor; low |
| `guzzlehttp/psr7` | 2.8.0 | 2.12.1 | Medium | Potential request/response parsing path | Same-major patch/minor; low |
| `league/commonmark` | 2.8.0 | 2.8.2 | Medium | Transitive runtime package; no direct Encore rendering path found | Patch; low |
| `symfony/http-foundation` | 7.4.3 | 7.4.13 | Medium | Runtime request/response foundation | Patch; low |
| `symfony/http-kernel` | 7.4.3 | 7.4.12 | High | Runtime kernel through Laravel | Patch; low |
| `symfony/mailer` | 7.4.3 | 7.4.12 | Medium | Runtime package present; outbound product mail not implemented | Patch; low |
| `symfony/mime` | 7.4.0 | 7.4.12 | High, Medium | Runtime mail dependency; no current outbound workflow | Patch; low |
| `symfony/polyfill-intl-idn` | 1.33.0 | 1.38.1 | Low | Transitive runtime compatibility code | Same-major; low |
| `symfony/process` | 7.4.3 | 7.4.5 | Medium | Runtime/development process abstraction | Patch; low |
| `symfony/routing` | 7.4.3 | 7.4.13 | Medium | Runtime routing path | Patch; low |
| `symfony/yaml` | 7.4.1 | 7.4.12 | Low | Primarily tooling/configuration; no public YAML parser found | Patch; low |
| `phpunit/phpunit` | 11.5.49 | 11.5.50 | High | Development and CI only | Patch; low |
| `psy/psysh` | 0.12.18 | 0.12.19 | Medium | Development console only | Patch; low |

Composer resolved additional compatible transitive updates while retaining every major-version constraint. No package was added or removed. `composer audit --locked` reports zero advisories.

### npm advisories remediated

| Package | Previous | Minimum/current fixed | Severity | Encore exposure | Upgrade and breaking risk |
| --- | --- | --- | --- | --- | --- |
| `axios` | 1.13.2 | 1.16.0 | High | Browser dependency present | Same-major; low-to-medium |
| `concurrently` | 9.2.1 | 9.2.4 | Critical chain | Development scripts only | Patch; low |
| `shell-quote` | 1.8.3 | 1.9.0 | Critical | Transitive development dependency | Same-major; low |
| `follow-redirects` | 1.15.11 | 1.16.0 | High chain | Transitive Axios runtime dependency | Same-major; low |
| `form-data` | 4.0.5 | 4.0.6 | High | Transitive Axios dependency; browser usage reduces exposure | Patch; low |
| `picomatch` | 2.3.1 / 4.0.3 | 2.3.2 / 4.0.5 | High | Build tooling only | Patch; low |
| `postcss` | 8.5.6 | 8.5.19 | Moderate | Build tooling only | Patch; low |
| `rollup` | 4.56.0 | 4.62.2 | High | Build tooling only | Same-major; low |
| `vite` | 7.3.1 | 7.3.6 | High | Development server/build tooling; dev server must not be public | Patch; low |

Only minimum compatible direct upgrades and the package manager's non-forced transitive remediation were applied. `npm audit` reports zero advisories. There are no remaining known Composer or npm advisories at this snapshot; audits are time-sensitive and run in CI on every push and pull request.

## Continuous integration

The verification-only workflow runs one fail-fast job on `push` and `pull_request` with read-only repository contents permission:

1. Checkout.
2. PHP 8.5 and required extensions.
3. Composer install from the lockfile.
4. Node.js 22 and `npm ci`.
5. PostgreSQL 18 service health check.
6. Composer and npm security audits.
7. Fresh migrations against PostgreSQL.
8. Full PHPUnit suite.
9. Pint formatting check.
10. Strict Composer validation.
11. Production Vite build.

The workflow does not deploy. Remaining CI gaps are static analysis, JavaScript tests, coverage thresholds, and automated upgrade-from-production migration fixtures.

## Provider replay protection

All three TicketPal write routes retain shared-secret authentication and now require a signed, fresh, uniquely identified event. `integration_events` is the database authority for idempotency. It stores no raw request payload, returns stable correlation IDs, encrypts the original response for bounded replay, rejects event-ID payload conflicts, prevents concurrent duplicate work, and bounds failed retries.

PostgreSQL verification exposed and corrected an exception-driven unique-conflict path that worked under SQLite but aborted enclosing PostgreSQL transactions. Registration now uses conflict-ignore insertion followed by locked resolution. The final PostgreSQL replay suite passes.

## Administrative audit logging

The following implemented actions are audited:

- organisation creation and update;
- organisation-user creation and update;
- show assignment and removal;
- read-only Encore support inspection;
- organisation review moderation.

Mutation evidence is recorded transactionally with allowlisted before/after state. Passwords and other sensitive key classes are excluded. Model updates and deletes are rejected. Audit data has no retention/archival job, database-role immutability, or external tamper evidence yet.

## Tenant authorization review

| Controller | Surface | Result |
| --- | --- | --- |
| `Admin/DashboardController` | Organisation dashboard/support data | `OrganisationPolicy::viewDashboard`; queries remain explicitly scoped |
| `Admin/OrganisationController` | Organisation, users, show ownership, support | `OrganisationPolicy` and `ShowPolicy`; nested ownership tested |
| `Admin/ReviewModerationController` | Review moderation | `ReviewPolicy::moderate`; cross-tenant denial is not-found |
| `Auth/AuthenticatedSessionController` | Login/logout | Authentication lifecycle; no tenant entity command requiring a policy |
| `Api/TicketPal/*` | Provider writes | Integration principal, signed-event middleware, and service/domain validation; user policies do not apply |
| `Api/ReviewController` | Invitation-authorized public submission | Single-use token proof and transactional validation; user policies do not apply |
| `Web/HomeController` | Public content | Approved/public query contract; no authenticated tenant access |
| `Web/ShowController` | Public show content | Approved/public query contract; no authenticated tenant access |
| `Web/ReviewSubmissionController` | Public form | Invitation proof context; no authenticated tenant access |

Policies added: `OrganisationPolicy`, `ShowPolicy`, and `ReviewPolicy`. No currently implemented session-authenticated tenant controller remains without a policy. Provider authorization remains application-wide rather than organisation-scoped.

## Migration verification

Verification used a dedicated empty PostgreSQL 18 database, `encore_sprint0_verify_01`, and did not modify the development database.

| Stage | Result |
| --- | --- |
| Run all 13 migrations on an empty database | Passed |
| Roll back the complete migration batch | Passed; all 13 `down()` paths completed |
| Re-run all migrations | Passed |
| Full test suite against PostgreSQL after re-migration | Passed: 34 tests, 160 assertions |

## Architecture compliance and remaining risks

### Critical

No unresolved Critical finding was identified in the repository audit.

### High

- **Provider credential blast radius:** one application-wide TicketPal secret can authorize all provider writes and has no key ID, overlapping rotation, revocation record, or organisation scope.
- **Production recoverability:** automated encrypted backups, restore exercises, recovery objectives, and disaster-recovery ownership are not implemented.
- **Provider rollout compatibility:** signed-event headers are a deliberate breaking security change. TicketPal and Encore must coordinate deployment; old clients will receive HTTP 401.

### Medium

- **Abuse protection:** provider endpoints, public review submission, and login lack purpose-specific rate and payload controls beyond field validation and global request-size infrastructure.
- **Stored replay secrets:** the invitation response contains the one-time raw token and is encrypted at rest for seven days. `APP_KEY` protection, backup encryption, key rotation, and access control are security-critical.
- **Unbounded operational tables:** no cleanup or retention enforcement exists for `integration_events` or `audit_logs`; continued growth will affect indexes, storage, backup volume, and support queries.
- **Audit defense in depth:** model immutability is bypassable through direct SQL or bulk query operations; least-privilege database roles and external/tamper-evident retention are absent.
- **Identity assurance:** no MFA, password reset, email-verification enforcement, SSO, session inventory, or forced session revocation exists for administrators.
- **Privacy:** email hashes are unkeyed and vulnerable to dictionary enumeration; review IP/user-agent hash fields are present but not populated and their intended retention is undefined.
- **Architecture drift:** show synchronization and invitation creation remain controller-led despite accepted incremental service-layer direction. They should move only during relevant capability work, not Sprint 0.
- **API governance:** the provider API remains unversioned and has no credential-rotation or deprecation policy.
- **Observability:** no centralized metrics, tracing, alerting, error tracking, provider-event dashboard, or correlation-aware log standard is configured.
- **Scalability:** provider processing remains synchronous and single-database. This is intentional while ADR-007/008 are Proposed, but bulk ingestion and independent retry workers are not supported.
- **Static assurance:** no PHP static analyzer, JavaScript test suite, mutation testing, or coverage threshold is configured.

### Low

- **Audit actor lifecycle:** deleting a user or organisation nulls the corresponding audit foreign key; snapshots retain identifiers, but a durable actor-label strategy is undefined.
- **Status integrity:** integration-event and audit action values are strings without database check constraints or enumerated application types.
- **Support ergonomics:** there is no administrative UI or command for provider-event investigation, replay expiry cleanup, or audit export. These are operational capabilities requiring separate approval.

## Recommendation

**Further work required before production.** The code is suitable for engineering review and merge once the TicketPal team confirms coordinated adoption of the signed request contract. Production launch should remain blocked on credential scoping/rotation, backup-and-restore proof, rate limiting, encryption-key operations, audit/event retention, administrative identity assurance, and baseline observability.
