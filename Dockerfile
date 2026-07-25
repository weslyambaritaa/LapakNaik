# --- Stage 1: PHP dependencies (needed by the frontend build too — Ziggy's
# JS glue lives inside vendor/tightenco/ziggy, not in node_modules) ---
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# --- Stage 2: build frontend assets (Vite/Vue) ---
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
COPY --from=vendor /app/vendor/tightenco ./vendor/tightenco
RUN npm run build

# --- Stage 3: PHP app ---
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd bcmath mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
