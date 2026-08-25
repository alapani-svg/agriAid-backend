#!/bin/sh
set -e

# Wait isn't strictly necessary when using docker-compose `depends_on` with
# healthchecks, but this keeps the container resilient to slow DB startup.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        php -r "exit(@fsockopen(getenv('DB_HOST'), (int) (getenv('DB_PORT') ?: 3306)) ? 0 : 1);" && break
        sleep 1
    done
fi

if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

if [ "$RUN_MIGRATIONS" = "1" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
