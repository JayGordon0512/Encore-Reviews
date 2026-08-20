# Encore Provider API v2 contract fixtures

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

Status: Proposed shared baseline, 4 August 2026.

These immutable fixtures define exact TicketPal request bytes and expected
Encore outcomes for the proposed Provider API v2 Release 1 hand-off. They do
not activate an endpoint, authorise implementation or contain production keys.

## Contract

- OpenAPI: [`../../../docs/03-API/contracts/provider-api-v2/openapi.yaml`](../../../docs/03-API/contracts/provider-api-v2/openapi.yaml)
- ADR inputs: proposed repository ADR-016 and ADR-017
- API paths: `/api/v2/integrations/review-invitation-eligibilities` and
  `/api/v2/integrations/review-invitation-withdrawals`
- body schema version: `2.0`
- fixture version: `2.0.0-proposed.1`

TicketPal sends paid/current-consent evidence. Encore resolves provider
mappings, records eligibility and owns invitation scheduling and delivery.
No invitation token appears in any request or response.

## Canonical signature

```text
METHOD + "\n" + ABSOLUTE_PATH + "\n" + X-Request-Timestamp + "\n" +
X-Request-Nonce + "\n" + lowercase_hex(SHA256(raw_request_body))
```

The canonical string has no final newline. Request body files have one final LF
byte, included in their byte count, digest and signature. Intentional retries
use a fresh nonce and timestamp but preserve the idempotency key and raw body.

## Coverage

The 15 ordered cases prove:

1. eligibility acceptance, identical retry and content conflict;
2. nonce replay, malformed evidence, invalid signature and stale timestamp;
3. unknown, revoked and expired credentials;
4. missing provider-to-performance mapping;
5. withdrawal acceptance, identical retry and unknown-target non-disclosure;
6. operation-scope denial.

Run the sequence against a fresh isolated PostgreSQL database. Authentication
and authorisation failures must create no domain or outbox state. The unknown-
target withdrawal must have the same status and response shape as an existing
target.

## Offline verification

```sh
php verify.php
```

The checker validates JSON, request bytes, SHA-256 digests, HMAC signatures,
v2 paths, case order and response correlation IDs. Each repository must also
run the manifest through its real provider/consumer contract suite.

## Test credentials

`test-credentials.json` contains deliberately public test-only secrets for an
active full-scope credential, revoked and expired credentials, and an active
eligibility-only credential. They must never be configured outside isolated
contract tests.

## Change control

After adoption, never silently alter these bytes. Any breaking change creates a
new contract and fixture version and is released jointly by TicketPal and
Encore. API version, body schema version and HMAC prefix are distinct version
dimensions; the HMAC prefix remains `v1=` for this contract.
