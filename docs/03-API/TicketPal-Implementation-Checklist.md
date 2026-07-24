# TicketPal Signed Integration Implementation Checklist

- Status: Delivery checklist for TicketPal Engineering
- Applicable current contract: [Encore HTTP API Reference](README.md)
- Proposed target context: [Provider API Specification v2](Provider-API-Specification-v2.md)
- Migration governance: [TicketPal Provider v2 Migration](../06-Roadmap/TicketPal-Provider-v2-Migration.md)

Items referring to `X-TicketPal-*` are required for the current implemented Encore contract. Items referring to `X-Provider-*`, inbound correlation IDs, Performance Completed, or Ticket Scanned are future v2 work and must not be marked production-complete until those contracts are approved and implemented.

## 1. Ownership and contract readiness

- [ ] TicketPal technical owner is named.
- [ ] Encore technical owner is named.
- [ ] Operational escalation contacts and hours are agreed.
- [ ] Current implemented routes are distinguished from proposed v2 routes.
- [ ] TicketPal has reviewed ADR-011 and ADR-014.
- [ ] The Interface Control Document responsibilities are approved.
- [ ] No unimplemented endpoint is included in the production delivery scope.
- [ ] Any proposed dual-authentication period has a separately approved security decision and expiry date.

## 2. Provider signing

- [ ] The TicketPal secret is obtained through an approved secure channel.
- [ ] The secret is stored in a secret manager or protected runtime configuration, never source control.
- [ ] JSON is serialized to UTF-8 bytes exactly once before signing.
- [ ] The canonical string is `<timestamp>.<event-id>.<raw-request-body>`.
- [ ] HMAC-SHA256 uses the TicketPal secret as key.
- [ ] The signature is lowercase hexadecimal, optionally prefixed with `sha256=`.
- [ ] The exact signed body bytes are transmitted without reformatting or mutation.
- [ ] `X-TicketPal-Secret` is sent for the current contract.
- [ ] `X-TicketPal-Signature` is sent for the current contract.
- [ ] Secret and signature values are redacted from logs, errors, traces, and support output.
- [ ] Shared positive and negative signature test vectors match Encore.

## 3. Event ID generation

- [ ] Every logical delivery receives one globally unique event ID before the first network attempt.
- [ ] The event ID is persisted durably before transmission.
- [ ] The ID begins with an alphanumeric character.
- [ ] The ID contains only letters, digits, `.`, `_`, `:`, or `-`.
- [ ] The ID is no longer than 255 characters.
- [ ] The same event ID is reused after timeout or ambiguous response.
- [ ] The same event ID is never reused for different payload bytes.
- [ ] A corrected logical delivery receives a new event ID only after the original outcome is reconciled.
- [ ] Event IDs are searchable in TicketPal operational tooling.
- [ ] `X-TicketPal-Event-ID` is populated for every current Encore request.

## 4. Timestamp generation and clock control

- [ ] `X-TicketPal-Timestamp` is a ten-digit Unix timestamp in UTC seconds.
- [ ] Timestamp creation occurs immediately before signature generation.
- [ ] Production hosts synchronize to a reliable time source.
- [ ] Clock offset is monitored and alerted before it approaches 300 seconds.
- [ ] Tests cover accepted positive and negative skew near the boundary.
- [ ] Retrying a stale delivery regenerates the timestamp and signature while preserving event ID and payload bytes.

## 5. Correlation IDs

- [ ] TicketPal records the Encore response `X-Correlation-ID` with its event ID.
- [ ] Correlation IDs are propagated through TicketPal internal logs and support tooling without payload leakage.
- [ ] Missing response correlation IDs are handled as an observability defect, not a reason to create a new event.
- [ ] Future `X-Correlation-ID` request generation remains disabled until Provider API v2 is approved and Encore accepts it.
- [ ] The future v2 implementation uses valid UUIDs and prevents accidental reuse across unrelated operations.

## 6. Retry logic

- [ ] Network timeouts and HTTP 500 use bounded exponential backoff with jitter.
- [ ] HTTP 409 processing responses honor `Retry-After`.
- [ ] HTTP 401 stops automatic retry until credentials, signature, timestamp, and clock are checked.
- [ ] HTTP 409 payload conflict stops automatic retry and opens reconciliation.
- [ ] HTTP 409 exhausted failure or expired/unavailable replay opens reconciliation.
- [ ] HTTP 422 stops retry until data is corrected and the original outcome is understood.
- [ ] HTTP 403 and 429 are not assumed as current TicketPal guarantees.
- [ ] Retry limits and maximum retry age match the approved ICD.
- [ ] Automatic retry never changes event ID or payload bytes.
- [ ] Retry exhaustion creates an operational alert with event and correlation IDs.

## 7. Replay handling

- [ ] A replayed success is accepted as success even when `created` is false or the original status/body is returned.
- [ ] `X-Provider-Event-Replayed: true` is captured when present.
- [ ] TicketPal compares replayed response data with its recorded operation.
- [ ] A lost response is recovered by retrying the identical event, not by creating a replacement event.
- [ ] TicketPal understands that current encrypted response replay expires after seven days by default.
- [ ] An expired replay response triggers reconciliation and does not authorize duplicate business processing.
- [ ] Payload-conflict handling preserves both local payload evidence and the original event ID without logging secrets/PII unnecessarily.

## 8. Endpoint contract tests

### Show upsert

- [ ] Required fields `provider_event_id`, `title`, and `ticket_url` are always supplied.
- [ ] Status values are limited to `upcoming`, `now_playing`, or `archived`.
- [ ] Optional null and omission behavior is tested.
- [ ] Create and update both correctly handle HTTP 200 and `created`.
- [ ] Organisation IDs are sent only under an explicitly authorized mapping.
- [ ] Domain show identity remains stable across updates.

### Performance upsert

- [ ] The referenced TicketPal show exists before its performance is sent.
- [ ] The show is assigned to an Encore Organisation before performance synchronization.
- [ ] `provider_performance_id` remains stable.
- [ ] `starts_at` and optional `ends_at` include unambiguous timezone offsets.
- [ ] `ends_at` is after `starts_at`.
- [ ] Venue names are consistent enough for Organisation-scoped slug resolution.
- [ ] Create and update both correctly handle HTTP 200 and `created`.
- [ ] Unknown show, unassigned show, and cross-show performance conflicts are tested as 422.

### Review invitation

- [ ] `performance_id` identifies an existing Encore performance.
- [ ] Email address collection and transmission have an approved lawful purpose and retention policy.
- [ ] Ticket/booking identifiers are minimized and sent only when required.
- [ ] HTTP 201 response token is handled as a secret and never logged.
- [ ] Replaying the same provider event returns the original invitation response without creating another invitation.
- [ ] TicketPal protects the returned token until it is delivered through the approved audience channel.

### Future endpoints

- [ ] Performance Completed remains disabled until its state machine and payload are approved and implemented.
- [ ] Ticket Scanned remains disabled until its ownership, privacy, retention, payload, and correction rules are approved and implemented.

## 9. Automated testing

- [ ] Unit tests cover canonical string construction.
- [ ] Unit tests cover signature success and one-byte payload changes.
- [ ] Unit tests cover event ID persistence across retries.
- [ ] Unit tests cover timestamp/skew classification.
- [ ] Contract tests cover every implemented endpoint's required and optional fields.
- [ ] Contract tests cover 401, 409, 422, and 500/network handling.
- [ ] Duplicate identical-payload tests prove one logical outcome.
- [ ] Duplicate changed-payload tests prove automatic retry stops.
- [ ] Tests confirm secrets, signatures, raw invitation tokens, and unnecessary PII are absent from logs.
- [ ] The joint end-to-end plan passes in the integration environment.

## 10. Deployment readiness

- [ ] Signing can be enabled independently by environment.
- [ ] Secrets are environment-specific and never copied from production to non-production.
- [ ] TicketPal and Encore release windows and owners are agreed.
- [ ] The authentication compatibility mode, if any, is documented and time-bounded.
- [ ] Clock, error-rate, retry, and delivery dashboards are ready.
- [ ] Alerts include event/correlation IDs and exclude sensitive payloads.
- [ ] Rollback preserves event IDs and cannot cause duplicate processing.
- [ ] Production configuration has been peer reviewed.
- [ ] The change record links the approved specification, ICD, ADRs, tests, and rollback plan.

## 11. Production validation

- [ ] A controlled signed show upsert succeeds.
- [ ] A controlled signed performance upsert succeeds.
- [ ] A controlled invitation request succeeds and its token is protected.
- [ ] Replaying a controlled event returns the original response and replay header.
- [ ] Invalid-signature monitoring detects a controlled negative check without exposing the secret.
- [ ] Encore and TicketPal can both find the operation by event and correlation IDs.
- [ ] Authentication failure, conflict, retry, and 5xx rates remain within agreed thresholds.
- [ ] No legacy-only traffic remains before legacy removal.
- [ ] Operational owners record go/no-go and observation-period evidence.
- [ ] Final activated behavior is reflected in the current API reference.
