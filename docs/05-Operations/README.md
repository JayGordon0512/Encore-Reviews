# Operations Runbook

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

Provider integration verification is governed by the [End-to-End Integration Test Plan](End-to-End-Integration-Test-Plan.md). Sprint 0 evidence and remaining production risks are recorded in the [hardening report](sprint-0-hardening-report.md).

## Supported development topology

The repository is configured for Laravel Sail and Docker Compose. The Compose application and PostgreSQL containers share the `sail` network.

| Service | Compose service | Exposed port | Current role |
| --- | --- | --- | --- |
| Laravel | `laravel.test` | 80; Vite 5173 | Application runtime |
| PostgreSQL | `pgsql` | 5432 | Primary relational database |
| Redis | `redis` | 6379 | Provisioned; no application-specific workflow documented |
| Mailpit | `mailpit` | 1025/8025 | Development delivery target for the disabled-by-default invitation issuer |
| Meilisearch | `meilisearch` | 7700 | Provisioned; public search is not implemented |
| pgAdmin | `pgadmin` | 5050 | Local database administration UI |

Named volumes persist PostgreSQL, Redis, and Meilisearch data.

## Initial setup

Requirements:

- Docker with Compose support;
- Composer for the initial dependency installation;
- Node.js and npm for frontend assets.

```bash
composer install
cp .env.example .env
```

For the Compose PostgreSQL service, configure the database section in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=<local-password>
```

Set an application key and a non-placeholder provider secret:

```dotenv
ENCORE_TICKETPAL_SECRET=<strong-shared-secret>
```

TicketPal must also sign every event as documented in the [HTTP API reference](../03-API/README.md). The application defaults to a 300-second signature window, three processing attempts, and seven-day encrypted response replay retention.

Start the stack and initialize the application:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
npm install
npm run build
```

Create the first Encore administrator:

```bash
./vendor/bin/sail artisan encore:create-super-admin admin@example.com --name="Encore Admin"
```

The command requests and confirms a password without accepting it as a command-line option.

## Daily development

```bash
./vendor/bin/sail up -d
npm run dev
```

## Invitation delivery operations

Invitation delivery is implemented but disabled by default. It requires two
supervised runtime processes in addition to the web application:

```bash
php artisan schedule:work
php artisan queue:work database --queue=invitations --tries=1 --timeout=60 --sleep=3
```

The scheduler dispatches only schedule UUIDs. The queue and failed-job payloads
must never contain reviewer email, display name or invitation tokens.

Before enabling issuing, configure a secure current token digest key, retain an
old key only during a controlled rotation overlap, and configure the same
approved delivery provider used by TicketPal. Encore must use its own verified
sender identity and separately rotatable credentials; sharing a provider does
not merge TicketPal and Encore delivery data or application ownership:

```dotenv
ENCORE_INVITATION_TOKEN_DIGEST_KEY=<random-secret>
ENCORE_INVITATION_PREVIOUS_TOKEN_DIGEST_KEYS=
MAIL_MAILER=<ticketpal-approved-mailer>
MAIL_FROM_ADDRESS=reviews@encorereviews.co.uk
MAIL_FROM_NAME="Encore Reviews"
```

Inspect due-work dispatch without exposing contact data:

```bash
php artisan encore:invitations:dispatch-due --limit=100
php artisan queue:monitor invitations:100
```

Activation requires monitoring for worker health, oldest scheduled work, queue
depth, `review_invitation_schedules.status = dead_lettered`, and
`failed_jobs`. Resolve the mail or configuration cause before rescheduling a
dead letter; never edit an invitation token digest or contact ciphertext.

Keep `ENCORE_PROVIDER_V2_INVITATION_ISSUING_ENABLED=false` until the supervised
processes, mail sender identity, monitoring, keys and controlled staging journey
have all been verified.

Useful checks:

```bash
./vendor/bin/sail ps
./vendor/bin/sail artisan about
./vendor/bin/sail artisan route:list --except-vendor
./vendor/bin/sail artisan migrate:status
```

Stop containers without deleting persistent volumes:

```bash
./vendor/bin/sail down
```

Do not add `-v` unless deletion of local persistent data is intentional.

## Artisan execution context

When `.env` uses `DB_HOST=pgsql`, database-aware Artisan commands must run inside Sail:

```bash
./vendor/bin/sail artisan migrate
```

Running `php artisan migrate` directly on the host attempts to resolve `pgsql` through host DNS and fails with `could not translate host name "pgsql"`. Host-based PHP would instead require `DB_HOST=127.0.0.1` and the forwarded PostgreSQL port; switching contexts is not the recommended routine.

## Database migrations

Preflight:

```bash
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail artisan migrate --pretend
```

Apply pending migrations:

```bash
./vendor/bin/sail artisan migrate
```

Production migration execution should use Laravel's non-interactive production flag where appropriate:

```bash
php artisan migrate --force
```

The repository does not define automated backup or restore jobs. A production operator must establish and test PostgreSQL backup, retention, encryption, and recovery procedures before relying on this service for production data.

## Continuous integration

`.github/workflows/ci.yml` runs on every push and pull request without deploying. The single fail-fast job uses PHP 8.5, Node.js 22, and a PostgreSQL 18 service. It installs locked dependencies and then runs dependency audits, all migrations, the full test suite, Pint, strict Composer validation, and the production Vite build.

The job has read-only repository-content permission and uses an isolated CI database. A passing workflow proves clean installation and migration on PostgreSQL; it does not prove upgrade behavior against production data, backup restoration, high availability, or deployment readiness.

## Testing and quality gates

The default local test configuration uses in-memory SQLite, array cache/session/mail, and the synchronous queue driver. CI additionally exercises the suite and migrations against PostgreSQL 18.

Run the complete test suite:

```bash
php artisan test
```

Run formatting verification:

```bash
./vendor/bin/pint --test
```

Apply formatting:

```bash
./vendor/bin/pint
```

Build production assets:

```bash
npm run build
```

Audit dependency advisories:

```bash
composer audit --locked
npm audit
```

There is no PHPStan, Psalm, Larastan, or JavaScript test configuration in the current repository. Pint, PHPUnit, and the production Vite build are the configured quality checks.

## Health and diagnostics

Laravel exposes the framework health route:

```text
GET /up
```

Container health:

```bash
./vendor/bin/sail ps
```

Application logs use Laravel's configured logging stack. During development, logs can be followed with:

```bash
./vendor/bin/sail artisan pail
```

No external metrics, tracing, uptime monitor, alert routing, error tracking, or centralized log aggregation is configured by application code.

## Operational security checklist

Before a non-development deployment:

- set `APP_ENV=production` and `APP_DEBUG=false`;
- generate and protect `APP_KEY`;
- replace placeholder database and TicketPal credentials;
- restrict database and administration ports at the network boundary;
- configure HTTPS and secure session settings;
- run and review migrations;
- establish database backups and recovery tests;
- define log retention and alerting;
- review super-admin membership and deactivate unused users.

This checklist identifies operator responsibilities; the repository does not automate these production controls.

## Troubleshooting

### `pgsql` cannot be resolved

Cause: a host-shell Artisan process is using the Compose-only hostname.

Resolution:

```bash
./vendor/bin/sail artisan <command>
```

### Sail reports Docker is not running

Start the Docker daemon or Docker Desktop, then verify:

```bash
./vendor/bin/sail ps
```

### TicketPal API returns 401

Verify that `ENCORE_TICKETPAL_SECRET` is configured, `X-TicketPal-Secret` matches, the HMAC uses the exact raw request body, and the sender clock is within the configured tolerance. Clear cached configuration after changing environment values:

```bash
./vendor/bin/sail artisan config:clear
```

### Performance sync returns 422

Check the validation response. Domain causes include a missing TicketPal show, a show without an organisation, or a provider performance ID already attached to another show. Show synchronization and organisation assignment must precede performance synchronization.

### Public review is absent

New reviews are pending. Confirm that an organisation administrator approved the review and that the show is not archived.
