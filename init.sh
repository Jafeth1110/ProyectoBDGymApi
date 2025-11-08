#!/bin/sh
set -e

echo "Starting Laravel application..."

# Set permissions
chmod -R 755 /home/site/wwwroot/storage
chmod -R 755 /home/site/wwwroot/bootstrap/cache

# Clear and cache config
cd /home/site/wwwroot
php artisan config:cache
php artisan route:cache

echo "Laravel initialization complete"
