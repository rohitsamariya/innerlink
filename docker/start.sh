#!/bin/sh
set -e

echo "Caching config..."
php artisan config:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
