#!/bin/bash
set -e
cd /var/www/html

if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
