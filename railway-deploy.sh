#!/bin/bash
set -e

echo "=== St. Mark SMS Railway Deploy ==="

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Create storage symlink
echo "Creating storage link..."
php artisan storage:link || true

# Cache config/routes/views for production
echo "Caching..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Deploy complete ==="
