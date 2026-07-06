#!/bin/sh
set -e

# Escreve as chaves JWT a partir das variáveis de ambiente
mkdir -p /var/www/keys
if [ -n "$JWT_PRIVATE_KEY_PEM" ]; then
  printf '%s\n' "$JWT_PRIVATE_KEY_PEM" > /var/www/keys/private.pem
fi
if [ -n "$JWT_PUBLIC_KEY_PEM" ]; then
  printf '%s\n' "$JWT_PUBLIC_KEY_PEM" > /var/www/keys/public.pem
fi

# Remove .env para que config:cache use apenas env vars do container (ECS)
rm -f /var/www/.env

# Limpa cache antigo antes de recachear
php artisan config:clear 2>/dev/null || true

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

exec "$@"
