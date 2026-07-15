# Security and Tenancy

## Security boundary summary

Encore has three caller classes:

| Caller | Authentication | Authorization boundary |
| --- | --- | --- |
| Public audience | None; review submission requires invitation proof | Public pages and `POST /api/reviews` |
| Organisation administrator | Laravel session | Active user, active organisation, organisation-owned data |
| Encore super administrator | Laravel session | Active user and `super_admin` role |
| TicketPal integration | Shared secret header | `/api/ticketpal/*` route group |

## TicketPal authentication

TicketPal routes require `X-TicketPal-Secret`. The middleware compares it with `ENCORE_TICKETPAL_SECRET` using `hash_equals`. Missing configuration, a missing header, or a mismatch returns HTTP 401 with:

```json
{
  "ok": false,
  "message": "Unauthorized"
}
```

The current implementation uses one application-wide TicketPal secret. It does not provide per-organisation credentials, key identifiers, rotation overlap, request signatures, replay protection, IP allow-listing, or provider-specific principals.

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

Organisation isolation is implemented explicitly:

- dashboard show queries use `whereBelongsTo($organisation)`;
- dashboard review queries traverse `review → performance → show → organisation`;
- review moderation loads the review's show and compares its `organisation_id` with the signed-in user's `organisation_id`;
- nested organisation-user mutations verify that the user belongs to the route organisation;
- show removal verifies that the show belongs to the route organisation;
- assignment accepts only currently unassigned shows;
- venue synchronization resolves venues by `organisation_id + slug`.

This is not automatic row-level tenancy. New organisation-sensitive queries must add explicit scoping and tests. PostgreSQL row-level security is not configured.

## Support access

Encore super administrators can open a selected organisation's dashboard using a read-only support route. The view hides moderation actions, and the moderation controller rejects super administrators with HTTP 403. The implementation does not impersonate the customer user or alter the authenticated principal.

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
- no administrative audit log;
- no MFA, SSO, or password reset workflow;
- no global tenant scope or database row-level security;
- no formal secret rotation workflow in the application;
- no documented production backup, retention, alerting, or incident-response integration.

Security improvements belong in the roadmap until implemented and tested.
