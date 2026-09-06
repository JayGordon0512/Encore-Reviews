# Release Documentation

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

Release records provide a durable account of what changed, which architecture is effective, how operators should deploy or recover, and which risks remain. They supplement Git history and do not replace executable tests, migrations, ADRs, API specifications, or operational runbooks.

## Release record naming

Create one Markdown file per release:

```text
docs/07-Releases/v<major>.<minor>.<patch>-<descriptive-name>.md
```

Use the released semantic version and a stable descriptive suffix. Do not rewrite published release records except to correct an explicit factual error; add a dated correction note when necessary.

## Required sections

Every future release record must contain:

### Release Notes

Summarize the release outcome, user/engineering value, scope, owners, effective date, and verification evidence.

### Architecture Changes

List new, superseded, accepted, or deprecated ADRs and explain whether the architectural baseline changed. State “None” when there is no architecture change.

### Operational Changes

Describe runtime, infrastructure, configuration, monitoring, backup, worker, secret, support, or incident-response changes.

### Breaking Changes

Identify incompatible API, integration, data, configuration, deployment, or behavior changes. Name affected consumers, coordination owner, activation window, deprecation path, and rollback constraints. State “None” explicitly when applicable.

### Migration Notes

Document schema/data migrations, compatibility phases, preconditions, commands, expected duration/locking, verification, rollback, and backup requirements. Never claim rollback safety without evidence.

### Known Issues

List unresolved defects, limitations, security risks, operational risks, workarounds, severity, owner, and follow-up reference.

### Deployment Notes

Define deployment order, configuration and secret prerequisites, health checks, smoke tests, monitoring window, go/no-go owner, and rollback procedure.

## Release governance

Before approval:

1. Confirm significant initiatives have the Strategic Review, Engineering Review, and Founder Approval required by the [Operating Principles](../00-Vision/Operating-Principles.md).
2. Confirm the release record matches code, schema, tests, API references, and operations documentation.
3. Update the [Decision Register](../Decision-Register.md) and ADR index for decision-status changes.
4. Update the Provider API Specification and migration programme for provider-contract changes.
5. Verify every local documentation cross-reference.
6. Identify documentation added, superseded, or intentionally retained for history.
7. Record test, migration, dependency-audit, formatting, build, and deployment evidence actually obtained.
8. Obtain architecture, security, operations, product, and provider approval where their boundaries are affected.

A release record must distinguish implementation completion, deployment completion, and production validation. None may be inferred from another.

## Release index

| Release | Status | Purpose |
| --- | --- | --- |
| [v1.7.0 Organiser Event Rescheduling](v1.7.0-organiser-event-rescheduling.md) | Implemented locally; activation pending | Lets independent organisers edit events, add dates and safely reschedule or cancel unsent review invitations. |
| [v1.6.0 Duration-Based Invitation Scheduling](v1.6.0-duration-based-invitation-scheduling.md) | Implemented locally; activation pending | Calculates review-email timing from performance start, duration and the approved post-event delay. |
| [v1.5.0 Invitation Scheduling Visibility](v1.5.0-invitation-scheduling-visibility.md) | Implemented locally; activation pending | Gives organisers privacy-safe delivery status and operators a controlled, audited release path for held invitations. |
| [v1.4.0 Staging Droplet Runtime](v1.4.0-staging-droplet-runtime.md) | Deployed to staging | Records the dedicated staging runtime, private database and supervised web, scheduler and invitation worker services. |
| [v1.3.0 Organiser Audience Invitations](v1.3.0-organiser-audience-invitations.md) | Implemented; activation pending | Adds encrypted CSV attendance import and independently gated organiser invitation scheduling. |
| [v1.2.0 Secure Invitation Entry](v1.2.0-secure-invitation-entry.md) | Implemented; activation prohibited | Keeps newly emailed invitation capabilities out of HTTP request URLs through a fragment/POST/session exchange. |
| [v1.1.0 Invitation Delivery Foundation](v1.1.0-invitation-delivery-foundation.md) | Implemented locally; activation prohibited | Adds Encore-owned, queued and retryable invitation email delivery behind the existing issuing control. |
| [v1.0.0 Provider API v2 Local Build](v1.0.0-provider-v2-local-build.md) | Implemented locally; activation prohibited | Records the disabled Release 1 provider hand-off implementation and remaining gates. |
| [v0.3.0 Enterprise Foundation](v0.3.0-Enterprise-Foundation.md) | Architectural baseline | Formally closes the Enterprise Foundation Programme and establishes the future-development baseline. |
