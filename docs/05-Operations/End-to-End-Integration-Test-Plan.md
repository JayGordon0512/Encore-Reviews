# End-to-End Provider Integration Test Plan

- Status: Authoritative verification plan
- Current executable scope: TicketPal show upsert, performance upsert, and review invitation
- Proposed scope: Provider API v2, Performance Completed, and Ticket Scanned after approval/implementation
- Owners: Encore Reviews Engineering and provider engineering

## 1. Purpose

This plan verifies authentication, replay safety, endpoint behavior, operational evidence, and cross-party recovery. It is designed for the current TicketPal implementation and future provider integrations without treating proposed capabilities as implemented.

The [Provider API Specification v2](../03-API/Provider-API-Specification-v2.md) defines technical behavior. The [Interface Control Document](../03-API/Interface-Control-Document.md) defines responsibilities. Test results do not change either contract.

## 2. Preconditions

- Use an isolated integration environment and non-production credentials.
- Synchronize provider and Encore clocks.
- Agree a unique test-run prefix for event IDs.
- Ensure observers can query `integration_events` without viewing encrypted response contents.
- Ensure observers can query `audit_logs` read-only.
- Prepare an Organisation, provider show, performance, and test email identity as required.
- Redact secrets, signatures, invitation tokens, email addresses, and ticket identifiers from retained evidence.
- Record request time, endpoint, event ID, response status, correlation ID, and database evidence for every scenario.

Current provider requests use `X-TicketPal-*`. Generic `X-Provider-*` headers apply only after v2 implementation.

## 3. Evidence model

### Provider event record

For a request registered by current middleware, verify:

- `provider = ticketpal`;
- expected route-derived `event_type`;
- expected external event ID;
- SHA-256 payload hash matches the exact request bytes;
- lifecycle status and attempts;
- received/processed timestamps as applicable;
- non-empty correlation UUID;
- failure classification contains no sensitive message or stack trace;
- response replay fields are present only for processed responses.

### Administrative audit record

Normal provider delivery does **not** currently create an `audit_logs` row. The expected audit result for provider scenarios is therefore “none,” unless a human administrator performs a separate privileged action. `integration_events` is delivery evidence under [ADR-014](../02-ADR/ADR-014-provider-event-store.md); `audit_logs` is privileged-human-action evidence under [ADR-012](../02-ADR/ADR-012-transactional-administrative-audit-logging.md).

This distinction must be changed in the specification before any future provider business-audit implementation.

## 4. Current mandatory scenarios

### E2E-01 — Valid signed request

**Objective:** Prove a correctly authenticated first delivery executes once.

**Procedure:** Send a new show or performance upsert with valid legacy secret, unique event ID, current timestamp, correct HMAC, and valid payload.

**Expected behavior:** Encore authenticates, registers, validates, processes, and returns a correlation ID.

**Expected HTTP:** 200. `X-Correlation-ID` present. No replay header.

**Expected audit record:** None.

**Expected provider event record:** One row, `status = processed`, `attempts = 1`, matching payload hash, populated `processed_at`, encrypted response body/status, and response expiry.

### E2E-02 — Invalid signature

**Objective:** Prove payload authentication fails before registration.

**Procedure:** Sign valid bytes, then change one payload byte or signature character before transmission.

**Expected behavior:** Encore rejects the request and performs no domain processing.

**Expected HTTP:** 401 with invalid-signature message. Current behavior does not guarantee a correlation header for pre-registration failures.

**Expected audit record:** None.

**Expected provider event record:** None.

### E2E-03 — Expired timestamp

**Objective:** Prove stale signed requests cannot enter replay registration.

**Procedure:** Create a correctly signed request whose timestamp is more than 300 seconds older than Encore's clock.

**Expected behavior:** Encore rejects freshness before event registration and domain processing.

**Expected HTTP:** 401.

**Expected audit record:** None.

**Expected provider event record:** None.

### E2E-04 — Clock-skew boundaries

**Objective:** Verify behavior on both sides of the configured ±300-second tolerance.

**Procedure:** Use distinct event IDs for timestamps comfortably inside positive/negative tolerance, then timestamps beyond both boundaries. Avoid exact-boundary timing races in automated evidence.

**Expected behavior:** Inside-window requests proceed to ordinary endpoint validation/processing. Outside-window requests are rejected before registration.

**Expected HTTP:** Inside: endpoint-appropriate 200/201/422. Outside: 401.

**Expected audit record:** None.

**Expected provider event record:** One per authenticated inside-window event; none for outside-window events.

### E2E-05 — Replay request after successful processing

**Objective:** Prove a processed event returns its original response without repeating domain work.

**Procedure:** Send a valid invitation event, record its response, then resend the same event ID and byte-identical payload with a fresh valid timestamp/signature.

**Expected behavior:** The invitation count does not increase on replay. The original token/body/status are recovered from encrypted response storage.

**Expected HTTP:** Original 201 and replayed 201; replay includes `X-Provider-Event-Replayed: true` and the same correlation ID.

**Expected audit record:** None.

**Expected provider event record:** One `processed` row, `attempts = 1`; no duplicate row.

### E2E-06 — Duplicate request with identical payload during processing

**Objective:** Prove concurrent delivery cannot execute domain processing twice.

**Procedure:** In an integration harness capable of pausing the first request after registration, submit an identical concurrent request with the same event ID.

**Expected behavior:** One request owns processing. The concurrent request does not enter the controller.

**Expected HTTP:** Concurrent request returns 409 with `Retry-After: 1` and the event correlation ID. The owning request later returns its endpoint result.

**Expected audit record:** None.

**Expected provider event record:** One row. It is `processing` during collision, then reaches the owning request's final lifecycle state.

### E2E-07 — Duplicate request with different payload

**Objective:** Prove event-ID reuse cannot mutate an already registered logical delivery.

**Procedure:** Process a valid event, then resend its event ID with changed payload bytes and a valid signature for those changed bytes.

**Expected behavior:** Encore rejects the conflicting payload and leaves original domain state unchanged.

**Expected HTTP:** 409 with payload-conflict message and original correlation ID.

**Expected audit record:** None.

**Expected provider event record:** One row retaining the original payload hash and lifecycle; no second row and no attempt increment.

### E2E-08 — Validation failure replay

**Objective:** Prove a deterministic non-5xx response is replayed consistently.

**Procedure:** Send an authenticated performance upsert for an unknown show, then repeat the identical event.

**Expected behavior:** The original validation response is stored as processed and replayed without re-entering validation/business processing.

**Expected HTTP:** Original 422; replayed 422 with replay header and same correlation ID.

**Expected audit record:** None.

**Expected provider event record:** One `processed` row with response status 422 and `attempts = 1`.

### E2E-09 — Administrative audit logging verification

**Objective:** Prove provider delivery evidence and privileged administrative audit evidence are not conflated.

**Procedure:** Complete a signed provider upsert. Separately, have an authenticated Organisation administrator moderate a resulting Organisation-owned review or have an Encore administrator perform a supported audited action.

**Expected behavior:** Provider delivery creates integration evidence only. The human administrative command creates an audit entry with actor, organisation, action, target, before/after state, request metadata, and its own correlation ID.

**Expected HTTP:** Provider operation: endpoint-appropriate 200/201. Administrative operation: current web redirect/success behavior.

**Expected audit record:** Exactly one for the selected administrative command; none for the provider request. No password, secret, token, authorization, or cookie fields.

**Expected provider event record:** Exactly one for the provider request; none for the administrative web action.

### E2E-10 — Correlation ID propagation

**Objective:** Prove both parties can trace a registered operation end to end.

**Procedure:** Send a valid signed request, capture the Encore response correlation ID, and search Encore's event record and provider logs using it.

**Expected behavior:** The response and event store use the same UUID. TicketPal associates it with the source event ID. Current Encore generates the correlation ID.

**Expected HTTP:** Endpoint-appropriate 200/201/422 with `X-Correlation-ID` after registration.

**Expected audit record:** None for the provider request.

**Expected provider event record:** One row with the response correlation UUID.

### E2E-11 — Failure recovery below retry limit

**Objective:** Prove a controlled server failure can retry without a duplicate event row.

**Procedure:** In a non-production fault-injection environment, cause the first authenticated event attempt to throw or return 500 before a durable domain result, then remove the fault and retry the same event ID and payload.

**Expected behavior:** First attempt becomes `failed`. A matching retry below the configured maximum re-enters processing. Successful recovery produces one domain outcome.

**Expected HTTP:** First 500; recovery returns endpoint-appropriate 200/201. If all three attempts fail, later replay returns 409 and stops automatic processing.

**Expected audit record:** None unless the fault/recovery includes a separate privileged human action.

**Expected provider event record:** One row; attempts increment for each admitted failed retry, final status `processed` on recovery or `failed` at exhaustion. Error text contains only sanitized classification.

### E2E-12 — Missing or invalid legacy secret

**Objective:** Prove current defense in depth rejects a signed request without the required legacy credential.

**Procedure:** Send an otherwise valid signed request with missing or incorrect `X-TicketPal-Secret`.

**Expected behavior:** Secret middleware rejects before event registration.

**Expected HTTP:** 401 with `Unauthorized`.

**Expected audit record:** None.

**Expected provider event record:** None.

## 5. Proposed endpoint scenarios

### E2E-P01 — Performance Completed

**Current status:** Blocked/not applicable. No endpoint exists.

**Test objective:** After future approval, prove an authenticated completion event applies exactly one valid performance state transition and handles duplicate, corrected, unknown, and out-of-order events.

**Expected current behavior:** A request to any assumed route is not a contractual operation and should return routing-level 404 if no unrelated route matches.

**Expected current HTTP:** 404, not 200/201.

**Expected current audit record:** None.

**Expected current provider event record:** None because no provider route/middleware is registered.

Before activation, replace this placeholder with approved route, payload, transition, status, audit, event-record, and correction expectations.

### E2E-P02 — Ticket Scanned

**Current status:** Blocked/not applicable. No endpoint or scan domain model exists.

**Test objective:** After future approval, prove authenticated attendance evidence is privacy-minimized, scoped to the correct Organisation/performance, idempotent, correctable, and retained according to policy.

**Expected current behavior:** A request to any assumed route is not a contractual operation and should return routing-level 404 if no unrelated route matches.

**Expected current HTTP:** 404, not 200/201.

**Expected current audit record:** None.

**Expected current provider event record:** None because no provider route/middleware is registered.

Before activation, replace this placeholder with approved route, fields, ownership, PII, retention, duplicate/reversal, status, audit, and event-record expectations.

## 6. Proposed v2 authentication scenarios

These scenarios remain blocked until generic v2 headers and credential scope are implemented:

- valid `X-Provider-*` authentication;
- unknown `X-Provider`;
- valid provider credential attempting another provider/Organisation's resource;
- inbound `X-Correlation-ID` validation and echo;
- active/next key overlap during rotation;
- revoked key rejection;
- approved 403 scope response;
- approved 429 quota response and `Retry-After`.

Each must be expanded to the same evidence standard before v2 activation.

## 7. Exit criteria

- Every current mandatory scenario passes or has a formally approved exception.
- Proposed/blocked scenarios are visibly recorded as not applicable, never silently passed.
- No duplicate domain outcome occurs.
- Both parties can reconcile by event and correlation IDs.
- Sensitive values are absent from captured logs and test evidence.
- Retry exhaustion and operational escalation are demonstrated.
- Database evidence matches HTTP evidence.
- Encore and provider engineering owners sign and date the results.

## 8. Test record template

| Field | Value |
| --- | --- |
| Test run ID |  |
| Environment |  |
| Contract version |  |
| Scenario |  |
| Provider event ID |  |
| Correlation ID |  |
| Request timestamp |  |
| Expected result |  |
| Actual result |  |
| Audit evidence |  |
| Provider event evidence |  |
| Sensitive-data review |  |
| Defect/exception reference |  |
| Encore approver/date |  |
| Provider approver/date |  |
