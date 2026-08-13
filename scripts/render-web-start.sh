#!/bin/sh
set -e

PORT="${PORT:-8080}"
export PORT

# Render injects configuration via environment variables; Laravel reads them directly.
if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required for production startup." >&2
    exit 1
fi

cd /var/www/html

# Storage paths are excluded from the Docker build context; create them at runtime.
mkdir -p \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/app/public \
    storage/app/private \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

mkdir -p /run/nginx /var/lib/nginx/tmp /var/log/nginx
envsubst '${PORT}' < /etc/nginx/templates/render.conf.template > /etc/nginx/http.d/default.conf

run_artisan() {
    su -s /bin/sh www-data -c "php artisan $*"
}

# Apply pending migrations before caching config (idempotent; safe for redeploys).
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    run_artisan migrate --force --no-ansi
fi

# Bootstrap production admin when credentials are supplied via Render env vars.
if [ -n "${ADMIN_PASSWORD:-}" ]; then
    run_artisan deploy:bootstrap-admin --no-ansi
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    run_artisan config:cache --no-ansi
    run_artisan route:cache --no-ansi
    run_artisan view:cache --no-ansi
else
    run_artisan config:clear --no-ansi || true
    run_artisan route:clear --no-ansi || true
    run_artisan view:clear --no-ansi || true
fi

php-fpm -D
exec nginx -g 'daemon off;'
