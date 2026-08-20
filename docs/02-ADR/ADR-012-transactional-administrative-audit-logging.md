# ADR-012: Transactional Administrative Audit Logging

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Accepted
- Date: 2026-07-15
- Scope: Privileged administrative activity

## Context

Encore administrators can create and manage organisations and users, assign shows, and inspect customer data for support. Organisation administrators can moderate reviews. These actions require durable attribution and before/after evidence without leaking credentials.

## Decision

Use an explicit `AuditLogger` service at administrative command boundaries. Mutation audit records are written inside the same database transaction as the state change. Read-only support access is recorded before the scoped dashboard is returned.

Audit records identify actor, organisation, action, entity, before/after allowlisted state, request IP address, user agent, correlation ID, and time. Sensitive key names are removed defensively. The Eloquent model rejects updates and deletes.

## Consequences

- A committed administrative mutation has corresponding audit evidence.
- Call sites make audit intent reviewable but must remember to invoke the service for each new privileged action.
- Audit state is deliberately selective rather than a complete model dump.
- Database roles, retention, export, tamper-evident storage, and cryptographic integrity remain operational follow-up work.

## Alternatives considered

### Model observers

Rejected because observers obscure actor and business-action context and can record incomplete or misleading changes from unrelated workflows.

### Middleware-only logging

Rejected for mutations because an HTTP status does not reliably describe the committed entity state or transaction outcome.

### External audit service

Not selected for the current monolith because it would introduce distributed transaction and availability concerns before an operational platform exists.
