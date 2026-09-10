FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --prefer-dist

# Debian glibc is required: free-search-mcp depends on Playwright wheels that
# have no musllinux tags. HTTP search engines do not need Chromium.
FROM php:8.4-fpm
WORKDIR /var/www/html

# Free Search MCP (stdio): uvx + free-search-mcp. Query is MCP tool JSON, never shell.
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

COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
