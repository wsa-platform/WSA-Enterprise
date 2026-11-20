#!/bin/sh
set -e
cd /var/www/html

# First-run staging: generate APP_KEY when .env exists but key is unset.
if [ -f .env ] && { [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; }; then
    php artisan key:generate --force --no-interaction
fi

# Rev 6.6 — ensure StableAnswerResultSet storage is writable by PHP-FPM workers
# before php-fpm accepts requests. Narrow scope: this directory tree only.
# Idempotent: creates missing dirs, repairs ownership/mode, never deletes data.
ensure_scientific_stable_results_storage() {
    target="storage/app/scientific_stable_results"
    parent="storage/app"

    if [ ! -d "$parent" ]; then
        mkdir -p "$parent"
    fi

    # Privileged repair only when the entrypoint still runs as root (php image default).
    # Non-root containers skip chown; directory creation still attempted.
    if [ "$(id -u)" -eq 0 ]; then
        mkdir -p "$target"
        # Parent must be traversable/writable so www-data can create org subdirs.
        chown www-data:www-data "$parent" 2>/dev/null || true
        chmod 0775 "$parent" 2>/dev/null || true

        chown www-data:www-data "$target"
        # setgid (2xxx): new org subdirs inherit www-data group on Linux filesystems.
        chmod 2775 "$target"

        # Repair existing organization directories only (depth 1). Do not touch file payloads.
        if [ -d "$target" ]; then
            find "$target" -mindepth 1 -maxdepth 1 -type d -exec chown www-data:www-data {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type d -exec chmod 2775 {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type f -exec chown www-data:www-data {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type f -exec chmod 0664 {} \;
        fi
    else
        mkdir -p "$target" || true
        chmod 2775 "$target" 2>/dev/null || true
    fi
}

ensure_scientific_stable_results_storage

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
