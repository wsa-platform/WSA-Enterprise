# syntax=docker/dockerfile:1.7
# WSA-Enterprise backend image
# Targets:
#   development (default) — composer with dev deps; used by local Compose (bind-mounted code)
#   production            — runtime-only deps; independent of host source bind mounts

# Pinned base tags for repeatable builds (override with --build-arg when needed).
ARG COMPOSER_IMAGE=composer:2.8.8
ARG PHP_IMAGE=php:8.4.14-fpm

# -----------------------------------------------------------------------------
# Stage: Composer dependency install
# -----------------------------------------------------------------------------
FROM ${COMPOSER_IMAGE} AS dependencies
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --prefer-dist

FROM dependencies AS production-deps
# Do NOT use --classmap-authoritative here: this stage only has composer.json/lock,
# so App\* classes are absent from the classmap. Authoritative mode would then
# refuse PSR-4 fallback after COPY . . and break Laravel route resolution.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# -----------------------------------------------------------------------------
# Stage: PHP runtime base (extensions + Free Search MCP)
# Debian glibc is required: free-search-mcp depends on Playwright wheels that
# have no musllinux tags. HTTP search engines do not need Chromium.
# -----------------------------------------------------------------------------
FROM ${PHP_IMAGE} AS runtime-base
WORKDIR /var/www/html

# UV pin kept literal: COPY --from does not expand stage-scoped ARG in this builder.
COPY --from=ghcr.io/astral-sh/uv:0.12.9 /uv /uvx /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        ca-certificates \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql zip bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

ENV UV_CACHE_DIR=/tmp/uv-cache \
    UV_TOOL_DIR=/opt/uv-tools \
    UV_TOOL_BIN_DIR=/opt/uv-tools/bin \
    UV_PYTHON_INSTALL_DIR=/opt/uv-python \
    PATH="/opt/uv-tools/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

RUN mkdir -p /var/cache/uv /opt/uv-tools /opt/uv-python \
    && uv python install 3.12 \
    && uv tool install --python 3.12 "free-search-mcp==0.11.0"

# Shared storage bootstrap used by both targets (Rev 6.6 invariants).
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# -----------------------------------------------------------------------------
# Target: development (default)
# -----------------------------------------------------------------------------
FROM runtime-base AS development
COPY --from=dependencies /app/vendor ./vendor
COPY . .
# Drop host package discovery caches (may reference require-dev providers).
# Laravel regenerates packages.php/services.php from vendor on first boot.
RUN mkdir -p \
        storage/app/public \
        storage/app/scientific_stable_results \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod 2770 storage/app/scientific_stable_results

# -----------------------------------------------------------------------------
# Target: production (independent runtime image)
# -----------------------------------------------------------------------------
FROM runtime-base AS production
COPY --from=production-deps /app/vendor ./vendor
COPY . .
# Do not run `php artisan package:discover` at build time: full artisan boot
# loads routes and can fail on app-route issues unrelated to packaging.
# Runtime PackageManifest rebuilds from the --no-dev vendor tree instead.
RUN mkdir -p \
        storage/app/public \
        storage/app/scientific_stable_results \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod 2770 storage/app/scientific_stable_results \
    && rm -f .env \
    && test ! -f .env \
    && test ! -d vendor/phpunit/phpunit \
    && test ! -d vendor/laravel/pail \
    && php -r 'exit(is_dir("vendor/laravel/framework") ? 0 : 1);'
