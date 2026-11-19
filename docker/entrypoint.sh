#!/bin/sh
set -e

# storage/ may be an empty or older volume: recreate the directories Laravel writes to.
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ -n "${DB_DATABASE:-}" ] && [ ! -f "$DB_DATABASE" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi

# Cache config at start, not at build, so it reflects this container's environment.
php artisan optimize --no-interaction || echo "entrypoint: optimize failed, continuing uncached" >&2

exec "$@"
