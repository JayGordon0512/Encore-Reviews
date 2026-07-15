# ADR-011: Signed Provider Event Ingestion

- Status: Accepted
- Date: 2026-07-15
- Scope: TicketPal write API authentication and replay resistance

## Context

The TicketPal API previously relied only on a static shared-secret header. That authenticated callers possessing the secret but did not bind authentication to a payload, prove freshness, or identify individual deliveries. Invitation creation is not naturally idempotent, so a repeated request could create duplicate records.

## Decision

Retain the existing shared-secret check and additionally require every TicketPal write request to provide `X-TicketPal-Event-ID`, a ten-digit Unix timestamp in `X-TicketPal-Timestamp`, and an HMAC-SHA256 signature in `X-TicketPal-Signature`.

The signed message is `<timestamp>.<event-id>.<raw-request-body>`. The existing TicketPal secret is the HMAC key. Timestamps outside the configured five-minute window are rejected. An authenticated request is registered in the provider event store before controller validation or business processing.

## Consequences

- Payload tampering and unsigned replay attempts are rejected before business processing.
- Provider clients must coordinate this breaking authentication-contract change.
- Clock synchronization is operationally required.
- One application-wide credential remains a compromise boundary; scoped credentials and rotation overlap are not implemented.
- Event identity and replay behavior are governed by ADR-014.

## Alternatives considered

### Replace the shared secret with HMAC only

Cleaner eventually, but removing the existing check during the same hardening change increases rollout risk. Both controls are required for this transition.

### Trust a caller-supplied idempotency key without signing

Rejected because an unauthenticated or altered event identifier would not provide payload integrity or freshness.

### Mutual TLS

Strong transport identity, but materially increases provider and infrastructure operation. It does not independently define application-level duplicate semantics.
