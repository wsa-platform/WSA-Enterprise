#!/bin/sh
set -e
cd /var/www/html

# First-run staging: generate APP_KEY when .env exists but key is unset.
if [ -f .env ] && { [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; }; then
    php artisan key:generate --force --no-interaction
fi

# Run database migrations before the application starts so required Laravel
# tables (including cache/cache_locks) exist on a fresh Render database.
# Retry briefly because the PostgreSQL service may still be starting.
run_migrations() {
    attempt=0
    max_attempts=30
    while [ "$attempt" -lt "$max_attempts" ]; do
        if php artisan migrate --force --no-interaction; then
            echo "Database migrations completed successfully."
            return 0
        fi
        attempt=$((attempt + 1))
        echo "Waiting for PostgreSQL/migrations ($attempt/$max_attempts)..."
        sleep 2
    done
    echo "Database migrations failed after $max_attempts attempts."
    return 1
}

run_migrations

# Queue workers require Redis; wait during startup to avoid crash-loops when
# Redis is still starting or temporarily unavailable on the Docker network.
wait_for_redis() {
    attempt=0
    max_attempts=30
    while [ "$attempt" -lt "$max_attempts" ]; do
        if php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo (int) Illuminate\Support\Facades\Redis::connection()->ping();
" 2>/dev/null | grep -q '^1$'; then
            return 0
        fi
        attempt=$((attempt + 1))
        echo "Waiting for Redis ($attempt/$max_attempts)..."
        sleep 2
    done
    echo "Redis unavailable after $max_attempts attempts."
    return 1
}

case "$*" in
    *queue:work*)
        wait_for_redis
        ;;
esac

exec "$@"
