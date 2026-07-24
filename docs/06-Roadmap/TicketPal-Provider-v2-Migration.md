# TicketPal Provider v2 Migration Programme

- Programme status: Proposed
- Implementation authority: None; documentation and coordination only
- Current release gate: TicketPal signed-request adoption
- Owners: Encore Reviews Engineering and TicketPal Engineering
- Last reviewed: 15 July 2026

## 1. Programme purpose

This programme coordinates TicketPal's move from the current provider-specific interface toward the proposed Provider API v2 contract. It is not an implementation specification and does not authorize security downgrade, new routes, or new product capabilities.

The [Provider API Specification v2](../03-API/Provider-API-Specification-v2.md) is the technical authority. The [Interface Control Document](../03-API/Interface-Control-Document.md) assigns cross-party responsibilities.

## 2. Current state

Encore currently exposes three unversioned TicketPal routes:

- show upsert;
- performance upsert;
- review invitation creation.

Each route requires both `X-TicketPal-Secret` and signed `X-TicketPal-*` event headers. Encore registers authenticated events, prevents duplicate processing, verifies payload hashes, generates a correlation ID, and retains an encrypted original response for bounded replay.

TicketPal signed-header adoption has not yet been confirmed. The generic `X-Provider-*` contract, inbound correlation IDs, provider-scoped credentials, Performance Completed, and Ticket Scanned are not implemented.

## 3. Target state

After formal v2 approval and implementation:

- TicketPal uses the provider-neutral authenticated contract;
- provider identity and credentials are scoped and rotatable;
- TicketPal supplies immutable event and correlation identifiers;
- both parties monitor and reconcile by event/correlation ID;
- legacy provider-specific authentication is removed after evidence-based cutover;
- only approved endpoint contracts are enabled.

Performance Completed and Ticket Scanned are outside the current migration scope until their domain, privacy, and API decisions are approved.

## 4. Entry criteria

The migration cannot begin until:

- named engineering and operational owners exist on both sides;
- the current API reference and proposed v2 specification have been reviewed;
- signing test vectors are agreed;
- credential exchange uses an approved secure channel;
- clocks and monitoring are suitable for ±300-second validation;
- retry and rollback ownership is documented;
- the end-to-end test environment is available.

## 5. Migration phases

### Phase 1 — Compatibility window

**Requested target:** Encore accepts legacy authentication and signed authentication during a time-bounded transition.

**Current reality:** Encore requires legacy secret authentication **and** signed authentication together. An either/or compatibility mode does not exist. Adding one would change the security model and therefore requires an approved ADR/specification update, implementation, security tests, telemetry, an expiry date, and a rollback plan.

If approved, Phase 1 must:

- define exactly which routes and environments permit each mode;
- prevent unsigned access from becoming an indefinite production path;
- record authentication mode without logging secrets;
- establish a fixed removal date and accountable owner;
- alert on legacy-only use;
- preserve event identity and replay protection wherever signed delivery is used.

**Preferred lower-risk alternative:** coordinate TicketPal's signed-header release in staging, then deploy it before or simultaneously with enforcement in production, avoiding a legacy-only production window.

**Exit criteria:** an approved security decision exists, compatibility behavior is tested, telemetry distinguishes modes, and rollback is rehearsed.

### Phase 2 — TicketPal upgrade

TicketPal implements and deploys:

- deterministic, persistent event ID generation;
- exact-byte HMAC-SHA256 signing;
- UTC Unix timestamps from synchronized clocks;
- correlation ID generation for the future v2 contract;
- response/replay classification;
- bounded retry with backoff and jitter;
- secret-protected configuration and redacted logging;
- searchable delivery evidence.

The upgrade should be feature-controlled so transmission can be enabled by environment without rebuilding. TicketPal must prove that serialization occurs before signing and that retrying does not change payload bytes or event identity.

**Exit criteria:** unit/contract tests pass, signing vectors match Encore, staging traffic is accepted, and no secrets or sensitive payloads appear in logs.

### Phase 3 — Integration verification

Run the [End-to-End Integration Test Plan](../05-Operations/End-to-End-Integration-Test-Plan.md) jointly. Capture evidence for signatures, skew boundaries, duplicate behavior, payload conflicts, response replay, failures, correlation, and recovery.

Performance Completed and Ticket Scanned scenarios must be recorded as blocked/not applicable until implemented; they must not be simulated as production guarantees.

**Exit criteria:** all applicable mandatory scenarios pass, defects are resolved or formally accepted, monitoring and incident contacts are live, and both engineering owners sign off.

### Phase 4 — Remove legacy authentication

This phase removes the provider-specific legacy path only after telemetry proves TicketPal no longer uses it and the v2 replacement is approved and operational.

Required controls:

- announce and record the removal window;
- verify zero legacy-only traffic for the agreed observation period;
- confirm current credentials and rollback material;
- remove compatibility code, configuration, and documentation together;
- run authentication and replay regression checks;
- monitor 401/409/5xx rates during and after cutover.

**Current constraint:** no legacy-only mode exists to remove independently. The actual removal scope will depend on the approved Phase 1 and v2 credential design.

## 6. Rollout risks

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Header enforcement precedes TicketPal deployment | All provider writes return 401 | Coordinated environment-specific activation and readiness check |
| JSON bytes change after signing | Signature rejection | Serialize once, sign exact bytes, transmit unchanged |
| Clock drift exceeds tolerance | Valid deliveries return 401 | NTP monitoring and skew alerting |
| Event IDs are regenerated during retry | Duplicate domain work under a new identity | Persist ID before first attempt and reuse it |
| Same ID is used for corrected payload | HTTP 409 and reconciliation incident | New logical correction only after original outcome is understood |
| Dual authentication remains indefinitely | Permanent downgrade path and operational ambiguity | Fixed expiry, telemetry, owner, and removal gate |
| Shared secret is exposed | All TicketPal provider writes are at risk | Secure storage, incident plan, coordinated replacement |
| Replay response expires before recovery | Original response unavailable without reprocessing | Align retry/escalation window with seven-day current retention |
| Unimplemented endpoints are assumed available | Data loss or failed rollout | Contract status labels, route verification, explicit scope control |

## 7. Rollback strategy

Rollback is phase-specific and must not discard event identities or cause duplicate business processing.

- **Before enforcement:** disable TicketPal signed transmission only if Encore still accepts the previous approved mode.
- **After enforcement:** prefer rolling forward by fixing signing/configuration. Re-enabling weaker authentication requires security-owner approval and a time limit.
- **After ambiguous responses:** retry the same event ID and byte-identical payload; never manufacture a replacement event merely to bypass replay state.
- **Credential issue:** coordinate replacement, preserve event records, and verify with a non-production health request before restoring traffic.
- **Contract defect:** stop the affected operation, retain delivery evidence, and reconcile by correlation/event ID.

Rollback must include communication, monitoring, decision owner, start/end time, and evidence that no deliveries were silently lost or duplicated.

## 8. Success criteria

The TicketPal migration is complete only when:

- TicketPal produces valid signatures for exact transmitted bytes;
- all applicable end-to-end scenarios pass in staging and production validation;
- event IDs are stable across retries and unique across logical deliveries;
- clock skew remains inside the accepted window;
- duplicate requests never repeat domain processing;
- correlation IDs are searchable by both parties;
- retry and escalation behavior matches the ICD;
- monitoring detects authentication, replay, conflict, and server failures;
- legacy authentication is removed or has a formally approved, expiring exception;
- documentation reflects the activated contract;
- Performance Completed and Ticket Scanned remain disabled unless separately approved and implemented.

## 9. Programme evidence

The migration owner must retain:

- approved contract and ICD versions;
- ADR approvals;
- signing test vectors;
- environment/configuration change records;
- end-to-end test evidence;
- monitoring screenshots or query evidence;
- cutover and rollback decisions;
- legacy-traffic observation evidence;
- final sign-off by Encore and TicketPal engineering owners.
