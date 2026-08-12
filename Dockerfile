# WSA-Enterprise — Render web service (Laravel API + React SPA)
# Reuses existing backend, frontend, and nginx routing patterns in a single container.

FROM node:22-alpine AS frontend-build
WORKDIR /app
ARG VITE_API_URL=/api/v1
ARG VITE_SHOW_DEMO_LOGIN=false
ENV VITE_API_URL=$VITE_API_URL
ENV VITE_SHOW_DEMO_LOGIN=$VITE_SHOW_DEMO_LOGIN
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader
COPY backend/ .
RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:8.4-fpm-alpine AS runtime
WORKDIR /var/www/html

RUN apk add --no-cache \
        nginx \
        postgresql-dev \
        libzip-dev \
        oniguruma-dev \
        bash \
        curl \
        gettext \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql zip bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && mkdir -p /run/nginx /var/lib/nginx/tmp

COPY --from=vendor /app /var/www/html
RUN mkdir -p \
        storage/logs \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/app/public \
        storage/app/private \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY --from=frontend-build /app/dist /usr/share/nginx/html
COPY nginx/render.conf.template /etc/nginx/templates/render.conf.template
COPY scripts/render-web-start.sh /usr/local/bin/render-web-start.sh
RUN sed -i 's/\r$//' /usr/local/bin/render-web-start.sh \
    && chmod +x /usr/local/bin/render-web-start.sh

ENV PORT=8080
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD sh -c 'curl -fsS "http://127.0.0.1:${PORT:-8080}/up" || exit 1'

CMD ["/usr/local/bin/render-web-start.sh"]
