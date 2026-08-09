#!/bin/sh
set -e
cd /var/www/html

# First-run staging: generate APP_KEY when .env exists but key is unset.
if [ -f .env ] && { [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; }; then
    php artisan key:generate --force --no-interaction
fi

exec "$@"
