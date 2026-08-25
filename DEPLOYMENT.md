# Deployment

The backend ships as a single Docker image (nginx + PHP-FPM, managed by
supervisord) plus a MySQL service, defined in `docker-compose.yml`.

## Files

- `docker/php/Dockerfile` — multi-stage build: installs Composer dependencies
  in a `composer:2` stage, then copies the app into a `php:8.3-fpm-alpine`
  runtime image with nginx + supervisord.
- `docker/php/entrypoint.sh` — waits for the database, links the `public/storage`
  symlink, runs migrations (when `RUN_MIGRATIONS=1`), and caches config/routes/views
  before starting supervisord.
- `docker/nginx/default.conf` — nginx site config (serves `public/`, proxies
  `*.php` to php-fpm on `127.0.0.1:9000`).
- `docker/supervisord.conf` — runs `nginx` and `php-fpm` as sibling processes
  in the same container.
- `docker-compose.yml` — `app` (the image above) + `mysql` services.
- `.env.production.example` — production environment template. Copy to `.env`
  and fill in every `CHANGE_ME` value; never commit the real `.env`.
- `.github/workflows/backend-ci.yml` — runs `php artisan test` and a Docker
  build check on every push/PR touching `agriAid-backend/`.

## Local production-like run

```bash
cp .env.production.example .env
# fill in APP_KEY (leave blank, generated below), DB_*, MAIL_*, SANCTUM_STATEFUL_DOMAINS, SESSION_DOMAIN

docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
```

The API is then served at `http://localhost:8000`.

## Deploying to a VPS / cloud host with Docker support

1. Push the repository (or just the `agriAid-backend` folder) to the host.
2. Copy `.env.production.example` to `.env` and fill in real secrets
   (`DB_PASSWORD`, `MAIL_*`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `APP_URL`, `FRONTEND_URL`).
3. `docker compose build && docker compose up -d`.
4. `docker compose exec app php artisan key:generate` (first deploy only).
5. Put a TLS-terminating reverse proxy (e.g. Caddy, Traefik, or your host's
   load balancer) in front of container port `8000`, since the container
   itself only serves plain HTTP.
6. Re-deploys: `docker compose build && docker compose up -d` — migrations run
   automatically on container start via `RUN_MIGRATIONS=1`.

## Deploying to a PaaS (Railway / Render / Fly.io)

These platforms build directly from `docker/php/Dockerfile` (point the
service's Dockerfile path at it) and provide their own managed MySQL —
set `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` from the
platform's connection details as environment variables (the same names used
in `.env.production.example`) instead of using `docker-compose.yml`, which is
only for self-hosted/VPS use.

## Storage

Uploaded files (avatars, etc.) are written to `storage/app/public`, exposed
via `php artisan storage:link` → `public/storage`. In `docker-compose.yml`
this directory is a named volume (`storage_data`) so uploads persist across
container restarts/rebuilds. If you outgrow single-host storage, switch
`FILESYSTEM_DISK` to an S3-compatible disk instead.

## CI

`.github/workflows/backend-ci.yml` runs the full test suite
(`php artisan test`, using SQLite in-memory per `phpunit.xml`) and verifies
the Docker image builds on every push/PR touching this backend. Wire a
deploy step (e.g. SSH + `docker compose pull && up -d`, or a PaaS deploy hook)
once you've chosen a hosting target.
