FROM php:8.3-fpm-alpine3.23 AS base

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" mysqli opcache \
    && pecl install apcu-5.1.24 \
    && docker-php-ext-enable apcu \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

# The Bitrix core lives in a named volume mounted at /var/www/html/bitrix;
# a fresh volume inherits the ownership of this directory. The installer unpacks
# the distributive into the document root.
RUN mkdir -p /var/www/html/bitrix /var/www/html/upload \
    && chown www-data:www-data /var/www/html /var/www/html/bitrix /var/www/html/upload

WORKDIR /var/www/html

FROM base AS app

# Owned by root: the application code is read-only for PHP.
COPY backend/local /var/www/html/local
COPY docker/installer /opt/installer

USER www-data
