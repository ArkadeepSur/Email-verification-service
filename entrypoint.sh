#!/bin/sh
set -e

# Run migrations
php artisan migrate --force

# Clear cache to ensure fresh configuration
php artisan cache:clear
php artisan config:cache

# Start PHP development server
php -S 0.0.0.0:${PORT:-8000} -t public
