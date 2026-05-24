# Contributing to Encore Reviews

Thanks for helping improve Encore Reviews. Please follow these steps:

1. Fork the repository and create a new branch for your work.
2. Install dependencies:
   - `composer install`
   - `npm install`
3. Run the test suite locally before submitting changes:
   - `php artisan test`
4. Build assets if you modify frontend code:
   - `npm run build`
5. Keep commits focused and meaningful.
6. Open a pull request against `main`.

### Notes

- Use `routes/api.php` for API route changes.
- Use `routes/web.php` for public page routes.
- Put Blade templates in `resources/views/public`.
- Database migrations belong in `database/migrations`.

If you make breaking changes, please document them in `README.md` or `SETUP.md`.
