#!/bin/bash
# set -e

# # Wait for MariaDB to be ready
# echo "Waiting for database to be ready..."
# max_attempts=30
# attempt=1
# while [ $attempt -le $max_attempts ]; do
#     if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" > /dev/null 2>&1; then
#         echo "Database is ready!"
#         break
#     fi
#     echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
#     sleep 2
#     attempt=$((attempt + 1))
# done

# # Run migrations
# echo "Running database migrations..."
# php artisan migrate --force

# # Start Apache
# echo "Starting Apache..."
# exec apache2-foreground

set -e

echo "Waiting for database to be ready..."

max_attempts=30
attempt=1

while [ "$attempt" -le "$max_attempts" ]; do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" >/dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi

    echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
    sleep 2
    attempt=$((attempt + 1))
done

if [ "$attempt" -gt "$max_attempts" ]; then
    echo "Database did not become ready."
    exit 1
fi

echo "Running database migrations..."
php artisan migrate --force

echo "Starting Apache..."
exec apache2-foreground
