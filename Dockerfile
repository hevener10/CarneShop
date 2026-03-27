ARG NODE_VERSION=20
ARG PHP_VERSION=8.3

FROM node:${NODE_VERSION}-bookworm-slim AS frontend-builder

ARG EXPO_PUBLIC_API_URL=http://localhost/api/v1
ENV CI=true
ENV EXPO_NO_TELEMETRY=1
ENV EXPO_PUBLIC_API_URL=${EXPO_PUBLIC_API_URL}

WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run web && test -f /app/frontend/dist/index.html

FROM php:${PHP_VERSION}-cli-bookworm AS backend-builder

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath zip xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY backend/ ./

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:${PHP_VERSION}-apache-bookworm AS runtime

ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/public

WORKDIR /var/www/html/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libpq-dev libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath zip xml opcache \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=backend-builder --chown=www-data:www-data /var/www/html/backend /var/www/html/backend
COPY --from=frontend-builder --chown=www-data:www-data /app/frontend/dist/ /var/www/html/backend/public/

RUN mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=5 CMD curl --fail --silent http://127.0.0.1/health || exit 1

EXPOSE 80
