# Encore Reviews staging Droplet

This stack deploys the `main` branch to a dedicated staging Droplet using the
same containerised operating pattern as TicketPal staging. It runs PHP 8.4,
Nginx, the database-backed invitation queue worker and the Laravel scheduler.
PostgreSQL runs as a private container with a persistent Docker volume, while
organiser artwork and verified daily database backups remain in Spaces.

The host Nginx server proxies `staging.encorereviews.co.uk` to the container
network through `127.0.0.1:8081`. Only ports 22, 80 and 443 should be publicly
reachable. Do not publish PostgreSQL or PHP-FPM ports.

Runtime secrets belong only in `/opt/encore-staging/shared/.env` on the server.
Start from `env.example`; never commit the populated file or copy production
customer data into staging. Keep both invitation-issuing flags disabled during
the migration.

## Server baseline

- Ubuntu 24.04 LTS, LON1, 2 GB RAM / 1 vCPU
- Docker Engine with the Compose plugin
- host Nginx and Certbot
- automatic security updates and DigitalOcean monitoring
- firewall allowing SSH, HTTP and HTTPS only
- a non-login `encore-deploy` deployment identity using the repository deploy key
- a daily systemd timer that verifies a PostgreSQL custom-format dump before
  uploading it to `database-backups/` in the Encore Spaces bucket

Clone the repository into `/opt/encore-staging/repository`, owned by
`encore-deploy`, and install the audited deployment wrapper as root:

```bash
install -o root -g encore-deploy -m 0750 \
  deploy/staging/deploy-encore-staging \
  /usr/local/sbin/deploy-encore-staging

visudo -cf deploy/staging/sudoers.encore-deploy
install -o root -g root -m 0440 \
  deploy/staging/sudoers.encore-deploy \
  /etc/sudoers.d/encore-deploy

install -o root -g root -m 0750 \
  deploy/staging/backup-encore-staging \
  /usr/local/sbin/backup-encore-staging
install -o root -g root -m 0644 \
  deploy/staging/encore-staging-backup.service \
  /etc/systemd/system/encore-staging-backup.service
install -o root -g root -m 0644 \
  deploy/staging/encore-staging-backup.timer \
  /etc/systemd/system/encore-staging-backup.timer
systemctl daemon-reload
systemctl enable --now encore-staging-backup.timer
```

Install `host-nginx.conf` under `/etc/nginx/sites-available/encore-staging`,
enable it, verify with `nginx -t`, and reload Nginx. Issue the TLS certificate
only after DNS points to the Droplet.

## Deployment

Routine deployments use the root-owned wrapper. The deploy identity is allowed
to run only this command through sudo:

```bash
sudo /usr/local/sbin/deploy-encore-staging
```

The wrapper accepts no arguments, serialises deployments, refuses a dirty
checkout, fast-forwards to the exact `origin/main` revision, builds immutable
images, runs migrations, clears caches and verifies web, worker and scheduler
container health.

## Migration order

1. Create and harden the dedicated Droplet.
2. Copy the existing App Platform settings into the root-only runtime file.
3. Start the private PostgreSQL service, restore a verified App Platform dump,
   and compare source and destination table counts.
4. Deploy while the existing App Platform application remains live.
5. Run and verify an initial Spaces database backup.
6. Test the Droplet directly using a temporary host-header override.
7. Lower DNS TTL, switch `staging.encorereviews.co.uk`, issue TLS and test again.
8. Run a controlled invitation test while organiser invitation issuing remains
   disabled by default.
9. Archive the App Platform application and remove its development database only
   after the monitoring window and a rollback decision point. Never delete the
   Spaces data.
