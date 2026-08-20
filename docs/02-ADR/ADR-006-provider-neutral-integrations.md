# ADR-006: Provider-Neutral Integrations

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Accepted
- Date: 2026-07-15
- Scope: Domain terminology and external integrations

## Context

TicketPal is the first provider integration, but Encore is an independent review platform intended to support organisations beyond TicketPal customers.

## Decision

Use provider-neutral core entities and store provider identity as metadata:

- `provider_source`
- `provider_event_id`
- `provider_performance_id`
- provider booking and ticket IDs on invitations

TicketPal-specific authentication, routes, controllers, requests, and services remain under TicketPal-specific namespaces and URL prefixes. Organisation is the ownership root.

## Consequences

- The core model is not named after TicketPal.
- TicketPal-specific assumptions remain visible and contained.
- The current code does not yet provide a generic provider interface; another integration would require deliberate design rather than pretending an abstraction already exists.
- Provider IDs require uniqueness within their defined provider key, not globally across all providers.

## Alternatives considered

### TicketPal-defined core entities

Rejected because Encore must support organisations and future data sources outside TicketPal.

### Remove provider metadata after import

Rejected because stable provider keys are required for idempotent updates and reconciliation.

### Build a generic provider framework immediately

Not selected. Only TicketPal is implemented, so a generic interface would be speculative. A second provider should trigger a concrete abstraction decision based on real common behavior.
