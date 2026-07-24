# Security and Tenancy

## Security boundary summary

Encore has three caller classes:

| Caller | Authentication | Authorization boundary |
| --- | --- | --- |
| Public audience | None; review submission requires invitation proof | Public pages and `POST /api/reviews` |
| Organisation administrator | Laravel session | Active user, active organisation, organisation-owned data |
| Encore super administrator | Laravel session | Active user and `super_admin` role |
| TicketPal integration | Shared secret plus signed event headers | `/api/ticketpal/*` route group and provider event identity |

## Identity, access, and contribution authority

Authentication establishes identity. Policies and tenant scope establish administrative access. Neither grants audience review authority.

Public review submission requires a valid, unused, unexpired invitation token and, when applicable, the matching email. The invitation is the bounded authority evidence for one performance-level contribution. A future audience account must preserve this separation under [ADR-015](../02-ADR/ADR-015-authority-through-verification.md).

## TicketPal authentication

TicketPal routes require `X-TicketPal-Secret` plus an event ID, fresh Unix timestamp, and HMAC-SHA256 signature over the timestamp, event ID, and raw body. Secrets and signatures are compared in constant time. Missing configuration, a missing header, an old timestamp, or a mismatch returns HTTP 401 with:

```json
{
  "ok": false,
  "message": "Unauthorized"
}
```

Authenticated events are registered before business processing. Database uniqueness prevents the same provider event from executing twice; conflicting payloads are rejected. Original JSON responses are encrypted for bounded replay, while raw request payloads are not retained. See [ADR-011](../02-ADR/ADR-011-signed-provider-event-ingestion.md) and [ADR-014](../02-ADR/ADR-014-provider-event-store.md).

The current implementation still uses one application-wide TicketPal secret. It does not provide per-organisation credentials, key identifiers, rotation overlap, IP allow-listing, or provider-specific principals.

## Administrative authentication

- Login uses the Laravel `web` guard with email and password.
- Passwords use Laravel's hashed cast.
- Login only succeeds for an active user.
- Non-super-admin login also requires an assigned, active organisation.
- Successful login regenerates the session ID.
- Logout invalidates the session and regenerates the CSRF token.
- Web mutation routes use Laravel CSRF protection.

There is no implemented self-registration, email verification enforcement, password reset UI, multi-factor authentication, or single sign-on.

## Roles

The `users.role` string currently has two meaningful values:

- `customer_admin`: belongs to an organisation and operates its dashboard.
- `super_admin`: has no organisation and can access Encore organisation management.

The role column is not a database enum. Code recognizes super administrators through an exact comparison with `super_admin`; all other active users are treated as organisation users.

## Tenant isolation

Organisation isolation uses Laravel Policies for administrative authorization plus explicit scoped queries:

- dashboard show queries use `whereBelongsTo($organisation)`;
- dashboard review queries traverse `review → performance → show → organisation`;
- `ReviewPolicy` validates review ownership and returns a not-found denial across tenant boundaries;
- `OrganisationPolicy` governs directories, lifecycle operations, support access, dashboards, and nested users;
- `ShowPolicy` validates assignment and removal against both the show and route organisation;
- assignment accepts only currently unassigned shows;
- venue synchronization resolves venues by `organisation_id + slug`.

This is not automatic row-level tenancy. New organisation-sensitive queries must add explicit scoping and tests. PostgreSQL row-level security is not configured.

## Support access

Encore super administrators can open a selected organisation's dashboard using a read-only support route. The view hides moderation actions, and the moderation policy rejects super administrators with HTTP 403. The implementation does not impersonate the customer user or alter the authenticated principal. Each support inspection creates an audit record.

## Administrative audit logging

Implemented administrative mutations create an `audit_logs` row inside the same transaction as the state change. Records identify the actor, organisation, action, entity, selected before/after state, request metadata, time, and correlation ID. Organisation/user management, show ownership assignment, review moderation, and read-only support inspection are covered.

Snapshots use explicit allowlists, and the logger defensively removes keys whose names indicate passwords, secrets, tokens, authorization data, or cookies. The Eloquent model rejects updates and deletes. Database-role restrictions, tamper-evident external retention, and a retention schedule are not yet implemented.

## Invitation and reviewer data

- Invitation tokens are stored as SHA-256 hashes.
- Normalized email addresses are stored as SHA-256 hashes on invitations and reviewers.
- The raw invitation token is returned only by invitation creation and is not recoverable from the stored hash.
- Review submission uses constant-time `hash_equals` for an invitation email match.
- Tokens are single-use and may expire; the default expiry is seven days.
- Reviews store optional display names and review content in plaintext.
- Review records have `ip_hash` and `user_agent_hash` columns, but the current submission code does not populate them.

Hashing low-entropy identifiers such as email addresses provides pseudonymization, not anonymity against dictionary attacks. No keyed HMAC or data-encryption layer is implemented.

## Public data exposure

Only reviews with `moderation_status = approved` are rendered on public show pages or included in public aggregates. Pending and rejected reviews are excluded. Public pages may show a reviewer's chosen display name, rating, submission date, tags, content, and recommendation-derived aggregates.

## Known security limitations

These are current-state limitations, not implemented controls:

- one shared TicketPal secret for the whole application;
- no rate limiting configured specifically for review or provider endpoints;
- no database-role or external tamper-evidence control for audit records;
- no MFA, SSO, or password reset workflow;
- no global tenant scope or database row-level security;
- no formal secret rotation workflow in the application;
- no documented production backup, retention, alerting, or incident-response integration.

Security improvements belong in the roadmap until implemented and tested.
