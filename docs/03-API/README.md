# HTTP API Reference

## Provider documentation hierarchy

| Document | Authority |
| --- | --- |
| [Provider API Specification v2](Provider-API-Specification-v2.md) | Single provider-integration contract authority; v2 target remains Proposed |
| This HTTP API Reference | Executable current-state route and payload reference |
| [Interface Control Document](Interface-Control-Document.md) | Formal sender/receiver responsibilities and provider agreement template |
| [TicketPal Implementation Checklist](TicketPal-Implementation-Checklist.md) | TicketPal engineering delivery and production-readiness checklist |
| [End-to-End Integration Test Plan](../05-Operations/End-to-End-Integration-Test-Plan.md) | Joint verification and evidence standard |

Where proposed v2 behavior differs from this reference, current code follows this reference until v2 is approved, implemented, tested, and activated.

## Scope and conventions

This document describes the JSON API implemented under `/api`. There is no API version prefix and no generated OpenAPI specification in the current repository.

- Requests should use `Content-Type: application/json`.
- Laravel validation failures return HTTP 422 with the standard `message` and `errors` object.
- Unexpected server failures use Laravel's configured exception behavior.
- IDs are Encore UUIDs unless stated otherwise.
- TicketPal endpoints currently return HTTP 200 for both created and updated upserts.

## TicketPal authentication and event identity

Every TicketPal request requires these headers:

```http
X-TicketPal-Secret: <ENCORE_TICKETPAL_SECRET>
X-TicketPal-Event-ID: <unique-provider-delivery-id>
X-TicketPal-Timestamp: <10-digit-unix-timestamp>
X-TicketPal-Signature: sha256=<hex-hmac>
```

Calculate the HMAC-SHA256 signature with `ENCORE_TICKETPAL_SECRET` over the exact bytes `<timestamp>.<event-id>.<raw-request-body>`. The `sha256=` prefix is optional. The timestamp must be within the configured tolerance, five minutes by default. Event IDs may contain letters, digits, `.`, `_`, `:`, and `-`, and are limited to 255 characters. All three TicketPal routes require both the existing secret and this event signature; callers must update before deploying this contract.

The protected endpoints are:

- `POST /api/ticketpal/shows/upsert`
- `POST /api/ticketpal/performances/upsert`
- `POST /api/ticketpal/invitations`

Missing or invalid credentials return HTTP 401:

```json
{
  "ok": false,
  "message": "Unauthorized"
}
```

## Provider idempotency and replay

`provider + X-TicketPal-Event-ID` identifies one delivery across all TicketPal routes. Encore stores a hash of the raw payload before processing:

- a duplicate with the same payload does not execute the controller again;
- while the original response is retained, a processed duplicate returns that response with `X-Provider-Event-Replayed: true`;
- reuse of an ID with different payload bytes returns HTTP 409;
- concurrent processing, exhausted failures, or an expired replay response return HTTP 409;
- a failed event may be retried up to three total attempts.

Every registered event has a stable `X-Correlation-ID`. Raw request payloads are not retained. Original response bodies are application-encrypted and retained for seven days by default. See [ADR-014](../02-ADR/ADR-014-provider-event-store.md).

## Upsert a TicketPal show

```http
POST /api/ticketpal/shows/upsert
```

Idempotency key: `provider_source = ticketpal` plus `provider_event_id`.

### Request fields

| Field | Required | Validation | Behavior |
| --- | --- | --- | --- |
| `provider_event_id` | Yes | string | TicketPal event identity |
| `title` | Yes | string | Always updated |
| `ticket_url` | Yes | URL | Always updated |
| `slug` | No | string | Used when creating; only fills an empty slug on update |
| `summary` | No | nullable string | Updated when present |
| `description` | No | nullable string | Updated when present |
| `genre` | No | nullable string | Updated when present |
| `status` | No | nullable enum | `upcoming`, `now_playing`, or `archived` |
| `primary_image_path` | No | nullable string | Updated when present |
| `ticket_url_source` | No | nullable string | Defaults to `ticketpal` |
| `organisation_id` | No | nullable UUID | Must reference an existing organisation; updated when present |

New shows default to `upcoming`. Encore generates a globally unique slug from the supplied slug or title, adding a numeric suffix when necessary.

### Example request

```json
{
  "provider_event_id": "event-123",
  "title": "A Live Show",
  "ticket_url": "https://tickets.example.com/events/123",
  "status": "upcoming",
  "organisation_id": "8fcd8210-441d-4c51-9799-05559e172f63"
}
```

### Success response

HTTP 200:

```json
{
  "ok": true,
  "show": {
    "id": "2f751988-ac22-4fc0-a854-e0179c52fe57",
    "slug": "a-live-show",
    "title": "A Live Show",
    "ticket_url": "https://tickets.example.com/events/123",
    "provider_source": "ticketpal",
    "provider_event_id": "event-123",
    "organisation_id": "8fcd8210-441d-4c51-9799-05559e172f63",
    "updated_at": "2026-07-15T12:00:00.000000Z"
  },
  "created": true
}
```

## Upsert a TicketPal performance

```http
POST /api/ticketpal/performances/upsert
```

Idempotency key: `provider_source = ticketpal` plus `provider_performance_id`.

The matching show must already exist and must belong to an organisation. The service resolves a venue inside that organisation by a normalized slug derived from `venue_name`.

### Request fields

| Field | Required | Validation | Behavior |
| --- | --- | --- | --- |
| `provider_event_id` | Yes | string, max 255 | Finds the TicketPal show |
| `provider_performance_id` | Yes | string, max 255 | Stable TicketPal performance identity |
| `starts_at` | Yes | date | Normalized to UTC; always updated |
| `venue_name` | Yes | string, max 255 | Resolves or creates the organisation venue |
| `ends_at` | No | nullable date after `starts_at` | Updated only when present |
| `status` | No | nullable string, max 255 | Updated only when present |
| `venue_city` | No | nullable string, max 255 | Updates the venue when present |
| `venue_postcode` | No | nullable string, max 255 | Updates the venue when present |

### Example request

```json
{
  "provider_event_id": "event-123",
  "provider_performance_id": "performance-456",
  "starts_at": "2026-09-01T19:30:00+01:00",
  "ends_at": "2026-09-01T21:30:00+01:00",
  "status": "scheduled",
  "venue_name": "Encore Theatre",
  "venue_city": "London",
  "venue_postcode": "W1D 6QF"
}
```

### Success response

HTTP 200:

```json
{
  "ok": true,
  "created": true,
  "performance": {
    "id": "3e0033c9-c404-48d5-8866-1fed0f7658bf",
    "show_id": "2f751988-ac22-4fc0-a854-e0179c52fe57",
    "venue_id": "b8a70544-5f48-444c-866c-efcbd64f1462",
    "status": "scheduled"
  }
}
```

On a repeat delivery, the same IDs are returned with `created: false`.

### Domain validation failures

HTTP 422 is returned when:

- no TicketPal show matches `provider_event_id`;
- the matching show is not assigned to an organisation;
- the provider performance ID already belongs to a different show.

## Create a review invitation

```http
POST /api/ticketpal/invitations
```

This endpoint creates a new invitation for a new provider event ID. Repeating the same signed provider event replays the original response and does not create another invitation.

### Request fields

| Field | Required | Validation | Default |
| --- | --- | --- | --- |
| `performance_id` | Yes | Existing performance ID | — |
| `email` | Yes | email | — |
| `provider_source` | No | string | `ticketpal` |
| `provider_booking_id` | No | nullable string | null |
| `provider_ticket_id` | No | nullable string | null |
| `attendance_state` | No | nullable string | null |
| `sent_at` | No | nullable date | current time |
| `expires_at` | No | nullable date | seven days from creation |
| `meta` | No | nullable object/array | null |

The normalized email and generated token are stored only as SHA-256 hashes. The raw token is returned in the response and cannot be recovered from the database.

### Success response

HTTP 201:

```json
{
  "ok": true,
  "invitation": {
    "id": "d16dc82f-2831-4df5-b249-d4f56614bc81",
    "performance_id": "3e0033c9-c404-48d5-8866-1fed0f7658bf",
    "sent_at": "2026-07-15T12:00:00.000000Z",
    "expires_at": "2026-07-22T12:00:00.000000Z",
    "token": "<48-character-generated-token>"
  }
}
```

## Submit an audience review

```http
POST /api/reviews
```

This endpoint does not use the TicketPal secret and does not require an Encore account. The invitation token is the contribution-authority evidence; the matching email, where required, validates the invitation's intended identity. Identity alone is insufficient under [ADR-015](../02-ADR/ADR-015-authority-through-verification.md).

### Request fields

| Field | Required | Validation |
| --- | --- | --- |
| `invitation_token` | Yes | string |
| `email` | Yes | email |
| `display_name` | No | nullable string |
| `rating` | Yes | integer from 1 to 5 |
| `would_recommend` | Yes | boolean |
| `tags` | No | array of strings |
| `content` | No | nullable string, maximum 2,000 characters |

### Processing rules

- The token must exist, be unused, and be unexpired when an expiry is set.
- When the invitation has an email hash, the submitted normalized email must match.
- The invitation row is locked during processing.
- A reviewer is reused by email hash or created if absent.
- A created review is verified with source `invitation` and moderation status `pending`.
- The invitation is marked used in the same transaction.

### Success response

HTTP 201:

```json
{
  "ok": true,
  "review": {
    "id": "7a42c518-a424-4865-87aa-f3553bc09f28",
    "performance_id": "3e0033c9-c404-48d5-8866-1fed0f7658bf",
    "rating": 5,
    "would_recommend": true,
    "submitted_at": "2026-07-15T12:30:00.000000Z"
  }
}
```

Invalid, expired, or already-used tokens return HTTP 422 with `Invalid or expired invitation token.` An email mismatch returns HTTP 422 with `Invitation token does not match this email address.`

## Compatibility and lifecycle

- The API is currently unversioned; breaking changes require coordinated provider changes.
- There is no bulk endpoint, pagination, or asynchronous acknowledgement.
- The API does not expose list or read endpoints.
- Provider retry safety applies to all TicketPal write routes through the event store. Review submission remains governed by single-use invitation-token consumption.
