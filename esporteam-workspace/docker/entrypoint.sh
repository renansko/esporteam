#!/bin/sh
set -e

echo "Running migrations..."
php artisan migrate --force

exec "$@"
