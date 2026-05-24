# Encore Reviews Setup

## Requirements

- PHP 8.2+
- Composer
- Node.js + npm
- SQLite (default local database)

## Local setup

```bash
git clone https://github.com/JayGordon0512/Encore-Reviews.git
cd Encore-Reviews
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## Database

Create a local SQLite database file:

```bash
touch database/database.sqlite
```

Update `.env` with the database path and TicketPal secret:

```env
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
ENCORE_TICKETPAL_SECRET=your-ticketpal-secret
```

If you prefer MySQL or PostgreSQL, update `DB_CONNECTION` and the related database settings.

Run migrations:

```bash
php artisan migrate
```

## Build and run

```bash
npm run build
php artisan serve
```

For development with asset hot reload:

```bash
npm run dev
```

## Tests

Run the test suite with:

```bash
php artisan test
```

## Project notes

- Public pages are in `routes/web.php`
- API routes are in `routes/api.php`
- Public views are under `resources/views/public`
- TicketPal secret is configured via `ENCORE_TICKETPAL_SECRET`
- Review submission uses `POST /api/reviews`
