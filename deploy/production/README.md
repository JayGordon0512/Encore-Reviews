# Encore Reviews production Droplet

Production runs on the dedicated `encore-production-01` Droplet and deploys
only the reviewed `main` branch. The stack uses private PostgreSQL, immutable
revision-tagged application images, an invitation queue worker and the Laravel
scheduler. PostgreSQL and PHP-FPM are never published publicly.

Runtime secrets belong only in `/opt/encore-production/shared/.env`, owned by
root with mode `0600`. Start from `env.example`. Every invitation and provider
activation gate must remain disabled during infrastructure deployment.

The root-owned deployment wrapper serialises releases, refuses dirty source,
fast-forwards only to `origin/main`, builds before cutover, verifies a database
dump before an upgrade, applies migrations, checks every runtime and restores
the previous application images if post-cutover verification fails.

Daily custom-format PostgreSQL dumps are verified with `pg_restore --list`,
retained locally for 14 days and protected by DigitalOcean daily Droplet
backups. Configure a separate Spaces copy before the platform stores material
production customer data.

DNS and TLS are the final cutover steps. Verify the application through the
Droplet IP with the production Host header before creating the DNS records.
After DNS resolves, issue the certificate for both `encorereviews.co.uk` and
`www.encorereviews.co.uk`, then re-run public and container health checks.
