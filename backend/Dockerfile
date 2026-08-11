FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --prefer-dist

FROM php:8.4-fpm-alpine
WORKDIR /var/www/html
RUN apk add --no-cache postgresql-dev libzip-dev oniguruma-dev bash $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql zip bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
