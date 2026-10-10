# syntax=docker/dockerfile:1

# ---------- Estágio 1: dependências PHP (Composer) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# As extensões PHP (gd, pgsql, etc.) existem no runtime; ignoramos platform
# reqs aqui apenas para resolver o grafo de dependências.
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --ignore-platform-reqs

# ---------- Estágio 2: build dos assets (Vite) ----------
# Base Debian (glibc) para que os binários nativos do Vite/rolldown sejam
# compatíveis com o runtime (também glibc). Evita erro musl vs glibc.
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# O Ziggy é referenciado em resources/js/app.js via ../../vendor/tightenco/ziggy.
COPY --from=vendor /app/vendor ./vendor
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./
RUN npm run build

# ---------- Estágio 3: runtime ----------
FROM php:8.4-cli-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libicu-dev libonig-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql zip gd intl bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Código da aplicação.
COPY . .
# Vendor PHP resolvido.
COPY --from=vendor /app/vendor ./vendor
# Assets de produção já compilados (public/build).
COPY --from=assets /app/public/build ./public/build

# Autoloader otimizado.
RUN composer dump-autoload --optimize --no-dev --no-interaction

# Permissões para storage e cache.
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
