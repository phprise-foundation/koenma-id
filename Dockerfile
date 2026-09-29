# syntax=docker/dockerfile:1

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM dunglas/frankenphp:1-php8.4-bookworm AS runtime

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=:80 \
    FRANKENPHP_CONFIG="worker ./public/index.php"

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN APP_SECRET=build \
    DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=16&charset=utf8" \
    JWT_PASSPHRASE=build \
    composer dump-env prod \
    && APP_SECRET=build \
       DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=16&charset=utf8" \
       JWT_PASSPHRASE=build \
       php bin/console cache:warmup --env=prod --no-interaction \
    && chown -R www-data:www-data var

COPY docker/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
