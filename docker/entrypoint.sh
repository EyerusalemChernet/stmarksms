#!/bin/bash
set -e

echo "======================================"
echo "  St. Mark SMS — Docker Startup"
echo "======================================"

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "→ Generating APP_KEY..."
    php artisan key:generate --force
fi

# Wait for MySQL to be ready (extra safety beyond healthcheck)
echo "→ Waiting for database..."
until php artisan db:monitor --max=1 2>/dev/null || \
      php -r "new PDO('mysql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);" 2>/dev/null; do
    echo "  Database not ready yet, retrying in 3s..."
    sleep 3
done
echo "  Database is ready."

# Run migrations
echo "→ Running migrations..."
php artisan migrate --force

# Create storage symlink
echo "→ Creating storage link..."
php artisan storage:link 2>/dev/null || true

# Cache for performance (skip in local dev if APP_ENV=local)
if [ "$APP_ENV" = "production" ]; then
    echo "→ Caching config/routes/views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "→ Clearing caches (dev mode)..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create supervisor log directory
mkdir -p /var/log/supervisor

echo "→ Starting Nginx + PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
