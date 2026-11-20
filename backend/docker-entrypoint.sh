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
        # Owner/group rwx only (no other). Prefer setgid (2770) so new org subdirs
        # inherit www-data group on filesystems that honor Unix mode bits; 9p bind
        # mounts may drop the setgid bit while retaining 0770 owner/group semantics.
        chmod 2770 "$target" 2>/dev/null || chmod 0770 "$target"

        # Repair existing organization directories and files (depth 1). Never world-writable.
        if [ -d "$target" ]; then
            find "$target" -mindepth 1 -maxdepth 1 -type d -exec chown www-data:www-data {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type d -exec chmod 2770 {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type f -exec chown www-data:www-data {} \;
            find "$target" -mindepth 1 -maxdepth 1 -type f -exec chmod 0660 {} \;
            # Org payload files: owner/group rw only (closes "other" read under 0664).
            find "$target" -mindepth 2 -maxdepth 2 -type f -name '*.json' -exec chmod 0660 {} \;
        fi
    else
        mkdir -p "$target" || true
        chmod 2770 "$target" 2>/dev/null || chmod 0770 "$target" 2>/dev/null || true
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
