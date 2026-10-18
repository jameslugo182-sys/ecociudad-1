# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html
RUN apk add --no-cache \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libwebp-dev \
        libzip-dev \
        linux-headers \
        fcgi \
        su-exec \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        pcntl \
    && docker-php-ext-enable opcache

COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --chown=www-data:www-data public/build /var/www/html/public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-ecociudad.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-ecociudad.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && APP_KEY=base64:dGVtcG9yYWwtYnVpbGQta2V5LXJlcGxhY2UtZW4tcnVudGltZQ== \
       php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && test -f public/build/manifest.json

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
