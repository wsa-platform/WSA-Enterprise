#!/bin/sh
set -e

PORT="${PORT:-8080}"
export PORT

# Render injects configuration via environment variables; Laravel reads them directly.
if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required for production startup." >&2
    exit 1
fi

mkdir -p /run/nginx /var/lib/nginx/tmp /var/log/nginx
envsubst '${PORT}' < /etc/nginx/templates/render.conf.template > /etc/nginx/http.d/default.conf

php artisan config:cache --no-ansi || true
php artisan route:cache --no-ansi || true
php artisan view:cache --no-ansi || true

php-fpm -D
exec nginx -g 'daemon off;'
