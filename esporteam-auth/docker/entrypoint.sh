#!/bin/sh
set -e

mkdir -p /var/www/keys
if [ ! -f /var/www/keys/private.pem ] || [ ! -f /var/www/keys/public.pem ]; then
	openssl genrsa -out /var/www/keys/private.pem 2048
	openssl rsa -in /var/www/keys/private.pem -pubout -out /var/www/keys/public.pem >/dev/null 2>&1
fi

echo "Running migrations..."
php artisan migrate --force

exec "$@"
