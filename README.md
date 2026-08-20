# Encore Reviews

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

Encore Reviews is an independent Laravel-based audience review platform for live events. TicketPal is one supported provider integration.

New contributors should begin with [The Encore Constitution](docs/00-Constitution/CONSTITUTION.md), the repository's highest governing authority, followed by the [Core Purpose](docs/00-Vision/CORE-PURPOSE.md), [Theory of Change](docs/00-Vision/THEORY-OF-CHANGE.md), [Manifesto](docs/00-Vision/The-Encore-Platform-Manifesto.md), and [Operating Principles](docs/00-Vision/Operating-Principles.md).

## Engineering handbook

The authoritative system, domain, API, security, operations, decision, and roadmap documentation is in the [Encore Reviews Engineering Handbook](docs/README.md).

[v0.3.0 Enterprise Foundation](docs/07-Releases/v0.3.0-Enterprise-Foundation.md) is the official architectural baseline for future development.

All pull requests must follow [CONTRIBUTING.md](CONTRIBUTING.md).

## What’s included

- Laravel backend with API and public pages
- Frontend using Vite and Tailwind-friendly CSS
- TicketPal integrations:
  - `POST /api/ticketpal/shows/upsert`
  - `POST /api/ticketpal/performances/upsert`
  - `POST /api/ticketpal/invitations`
- Review submission endpoint:
  - `POST /api/reviews`
- Public site pages:
  - `/` home
  - `/shows` show listing
  - `/shows/{slug}` show detail
  - `/review/submit` review submission form
- UUID-based schema for shows, performances, venues, reviewers, reviews, and invitations
- Feature tests covering key API and public page functionality
- Organisation-scoped customer dashboard with review moderation
- Encore super-admin area for organisations, users, show ownership, and support views

## Quick start

```bash
git clone https://github.com/JayGordon0512/Encore-Reviews.git
cd Encore-Reviews
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Local database setup

This project defaults to SQLite for local development.

```bash
touch database/database.sqlite
```

Then set these values in `.env`:

```env
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
ENCORE_TICKETPAL_SECRET=your-ticketpal-secret
```

Run migrations:

```bash
php artisan migrate
```

Create the first Encore administrator (the password is requested securely):

```bash
php artisan encore:create-super-admin admin@example.com --name="Encore Admin"
```

Build assets:

```bash
npm run build
```

Serve locally:

```bash
php artisan serve
```

## Testing

Run the feature test suite with:

```bash
php artisan test
```

## Notes for another machine

- API routes are in `routes/api.php`
- Web pages are in `routes/web.php`
- Public blade templates are under `resources/views/public`
- The homepage, show listing, show detail, and review submission views are already wired
- The TicketPal secret is configured via `ENCORE_TICKETPAL_SECRET`

## Administration

- Customers sign in at `/login` and only see shows and reviews owned by their organisation.
- Encore super admins are redirected to `/admin/encore/accounts` after login.
- Super admins can activate/deactivate organisations and users, assign imported shows, and open a read-only organisation support view.
- TicketPal show upserts accept an optional `organisation_id`; unassigned shows can also be assigned in the Encore admin area.

## Next steps

- Wire TicketPal event sync into performance/invitation creation
- Add search/filter for public shows

## License

MIT
