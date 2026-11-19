# syntax=docker/dockerfile:1

# Production image: FrankenPHP (Caddy + PHP) in classic mode, running as www-data.
# Build: docker build -t app .   Run: see compose.yaml and the README "Docker" section.

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22

FROM node:${NODE_VERSION}-trixie-slim AS node

# --- base: PHP runtime shared by every stage -------------------------------
FROM dunglas/frankenphp:1-php${PHP_VERSION}-trixie AS base

WORKDIR /app

RUN install-php-extensions \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        redis \
        zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

# --- vendor: production Composer dependencies -------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# --- assets: Vite build (Wayfinder calls `php artisan`, so PHP is needed) ----
FROM vendor AS assets

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY . .
RUN composer dump-autoload --no-dev --no-interaction \
    && npm run build

# --- production: final runtime image ---------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:8080

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --no-interaction --classmap-authoritative \
    && rm -f /usr/bin/composer \
    && ln -sfn /app/storage/app/public /app/public/storage \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /data/caddy /config/caddy

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/healthcheck.php /usr/local/share/app/healthcheck.php

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD ["php", "/usr/local/share/app/healthcheck.php"]

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
