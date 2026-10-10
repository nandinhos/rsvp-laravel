#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] Aguardando PostgreSQL em ${DB_HOST}:${DB_PORT}..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);"; do
    sleep 1
done
echo "[entrypoint] PostgreSQL disponível."

# Migrations (idempotente) e seed na primeira subida.
php artisan migrate --force

if [ "${DB_SEED:-false}" = "true" ]; then
    echo "[entrypoint] Executando seeders..."
    php artisan db:seed --force || echo "[entrypoint] Seed já aplicado ou falhou (ignorando)."
fi

# Caches de produção.
php artisan config:cache
php artisan route:cache

exec "$@"
