# Provider Catalogue Import API v2

- Status: implementation candidate; disabled by default
- Schema version: `2.0`
- Date: 21 August 2026

This contract imports provider-owned catalogue metadata into Encore without
bookings, attendees, payment data, ticket data or historical invitations.
TicketPal is the first provider, but the boundary and mappings are provider
neutral.

## Environments and activation

The deployment owner must provide the two values below through the release
handover; they are not source-code constants:

| Environment | API base URL | Credential |
| --- | --- | --- |
| Staging | To be supplied by Encore operations | Dedicated staging key ID and secret |
| Production | To be supplied by Encore operations after reconciliation | Different production key ID and secret |

All routes are unavailable while `ENCORE_PROVIDER_V2_INGRESS_ENABLED=false`.
Keep `ENCORE_PROVIDER_V2_INVITATION_ISSUING_ENABLED=false` throughout the dry
run, staging import and reconciliation. With issuing disabled, accepted review
eligibility is recorded as suppressed and no invitation is made issuable.

Credential operation scopes are:

- `catalogue-organisation:write`
- `catalogue-membership:write`
- `catalogue-show:write`
- `catalogue-performance:write`

## Request security

Every request uses `Content-Type: application/json` and these headers:

- `X-Provider-Key-Id`
- `X-Request-Timestamp` — RFC 3339 UTC and within the configured tolerance
- `X-Request-Nonce` — a new UUID for every HTTP attempt
- `X-Request-Signature` — `v1=` plus lowercase HMAC-SHA256 hex
- `Idempotency-Key` — retained for retries of the same logical operation
- `X-Correlation-Id` — UUID supplied by TicketPal and echoed by Encore

The signed bytes are:

```text
METHOD + "\n" + ABSOLUTE_PATH + "\n" + X-Request-Timestamp + "\n" +
X-Request-Nonce + "\n" + lowercase_hex(SHA256(raw_request_body))
```

The path includes `/api/v2` and excludes the hostname, query and fragment.
Retry an ambiguous request with the same idempotency key and byte-identical
body, but a fresh timestamp, nonce, correlation ID and signature.

## Common response

Successful synchronous upserts return HTTP 202:

```json
{
  "status": "accepted",
  "resource_type": "show",
  "action": "created",
  "mapping": {
    "provider_organisation_id": "TP-ORG-1",
    "provider_show_id": "TP-SHOW-100",
    "organisation_id": "<encore-uuid>",
    "show_id": "<encore-uuid>"
  },
  "correlation_id": "<request-correlation-uuid>"
}
```

An identical retry returns HTTP 202 with `status: duplicate`,
`action: unchanged` and the same Encore mapping. Reusing an idempotency key
with different request bytes returns HTTP 409 `idempotency_conflict`.
Attempting to move an existing external key to another parent returns HTTP 409
`mapping_conflict`. A missing parent mapping returns HTTP 422.

## Organisation upsert

`POST /api/v2/integrations/catalogue/organisations`

```json
{
  "schema_version": "2.0",
  "provider": "ticketpal",
  "provider_organisation_id": "TP-ORG-1",
  "name": "Example Theatre Company",
  "status": "active"
}
```

Accepted statuses: `active`, `inactive`, `archived`, `deleted`. Archived and
deleted records are retained as tombstones so their permanent TicketPal keys
cannot be reused or duplicated.

## Organisation-user membership upsert

`POST /api/v2/integrations/catalogue/organisation-user-memberships`

```json
{
  "schema_version": "2.0",
  "provider": "ticketpal",
  "provider_organisation_id": "TP-ORG-1",
  "provider_user_id": "TP-USER-9",
  "name": "Alex Owner",
  "email": "alex@example.com",
  "role": "owner",
  "status": "active"
}
```

Accepted roles are `owner` and `administrator`. TicketPal should send active
owners and administrators only for the initial catalogue import. Encore stores
organisation membership separately from the user's primary dashboard
organisation, so one person may manage several organisers. Imported users
receive a non-shareable random password and cannot authenticate with TicketPal
credentials; account activation/reset is a separate Encore workflow. A
TicketPal record cannot claim an Encore super-administrator account.

## Show upsert

`POST /api/v2/integrations/catalogue/shows`

```json
{
  "schema_version": "2.0",
  "provider": "ticketpal",
  "provider_organisation_id": "TP-ORG-1",
  "provider_show_id": "TP-SHOW-100",
  "title": "Example Show",
  "description": "The provider-owned public description.",
  "category": "theatre",
  "status": "archived",
  "image_url": "https://ticketpal.example/images/100.jpg",
  "public_url": "https://ticketpal.example/shows/100"
}
```

Accepted statuses: `draft`, `published`, `upcoming`, `now_playing`, `ended`,
`cancelled`, `archived`, `deleted`. `ended`, `cancelled`, `archived` and
`deleted` shows are stored as archived and review-locked. Provider deletion is
a tombstone update, not a destructive database delete.

## Performance upsert

`POST /api/v2/integrations/catalogue/performances`

```json
{
  "schema_version": "2.0",
  "provider": "ticketpal",
  "provider_show_id": "TP-SHOW-100",
  "provider_performance_id": "TP-PERF-100-A",
  "starts_at": "2024-06-01T19:30:00Z",
  "ends_at": "2024-06-01T21:30:00Z",
  "status": "completed",
  "location": {
    "type": "venue",
    "name": "Example Theatre",
    "city": "London",
    "postcode": "W1D 6QF",
    "country": "GB",
    "public_url": "https://example-theatre.test"
  }
}
```

Accepted statuses: `scheduled`, `completed`, `cancelled`, `archived`,
`deleted`. Location type is `venue` or `activity`. `ends_at`, city, postcode,
country and location public URL are optional; all timestamps should carry an
explicit offset.

## Import order and reconciliation

1. Keep v2 ingress and invitation issuing disabled while credentials are
   provisioned.
2. Enable ingress in staging only; keep invitation issuing disabled.
3. TicketPal supplies dry-run counts for organisations, active owner/admin
   memberships, shows and performances.
4. Import organisations, memberships, shows and performances in that order.
5. Compare accepted/duplicate/error totals and reconcile external-to-Encore
   mappings.
6. Repeat the same staging import to prove no duplicate domain rows are made.
7. Provision a different production key ID and secret, then repeat with
   production ingress enabled and invitation issuing still disabled.
8. Invitation issuing is a separate, explicit production decision after
   reconciliation.

Bookings, customers, attendees, orders, tickets, payments and historical
review invitations are outside this contract and must not be sent.
