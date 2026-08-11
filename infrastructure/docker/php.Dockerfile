# syntax=docker/dockerfile:1
#
# core-api runtime. PHP 8.4 to satisfy both Laravel 12/13 and the test
# framework (see docs/architecture/09-dependency-policy.md §2).
#
# bcmath is enabled explicitly: it is absent from the base image and several
# money/decimal paths expect it. Money is never a float regardless.
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache postgresql-dev icu-dev libzip-dev oniguruma-dev linux-headers \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql bcmath intl zip opcache pcntl \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Dependencies -----------------------------------------------------------
FROM base AS vendor
COPY services/core-api/composer.json services/core-api/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Production image -------------------------------------------------------
FROM base AS production
COPY --from=vendor /app/vendor ./vendor
COPY services/core-api ./
COPY packages/api-contracts /packages/api-contracts
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && chown -R www-data:www-data storage bootstrap/cache

# Opcache settings suited to an immutable image: the code never changes at
# runtime, so revalidation is pure overhead.
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.memory_consumption=192'; \
      echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# --- Local development ------------------------------------------------------
# Keeps dev dependencies and revalidates opcache, because the source is mounted.
FROM base AS development
RUN echo 'opcache.validate_timestamps=1' > /usr/local/etc/php/conf.d/opcache.ini
EXPOSE 9000
CMD ["php-fpm"]
