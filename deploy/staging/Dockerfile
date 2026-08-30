# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS frontend

WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    export COMPOSER_CACHE_DIR=/tmp/composer-cache; \
    composer install \
        --no-autoloader \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist

COPY . .
COPY --from=frontend /src/public/build ./public/build
COPY deploy/staging/php.ini /usr/local/etc/php/conf.d/encore.ini

RUN composer dump-autoload \
        --no-dev \
        --no-interaction \
        --optimize \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php -r "require 'vendor/autoload.php';" \
    && composer check-platform-reqs --no-dev

USER www-data

CMD ["php-fpm", "-F"]

FROM nginx:1.28-alpine AS web

COPY --from=app /var/www/html/public /var/www/html/public
COPY deploy/staging/nginx-container.conf /etc/nginx/conf.d/default.conf
