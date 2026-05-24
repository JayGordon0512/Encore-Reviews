# Encore Reviews

Encore Reviews is a Laravel-based audience review platform for live events, powered by TicketPal ticket data.

## What’s included

- Laravel backend with API and public pages
- Frontend using Vite and Tailwind-friendly CSS
- TicketPal integrations:
  - `POST /api/ticketpal/shows/upsert`
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

## Next steps

- Add organiser / admin dashboard
- Add review moderation actions
- Wire TicketPal event sync into performance/invitation creation
- Add search/filter for public shows

## License

MIT
