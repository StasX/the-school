#!/bin/sh
set -eu

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs \
    storage/infection bootstrap/cache uploads

composer install --prefer-dist --no-interaction --no-progress
php artisan config:clear

chown -R www-data:www-data storage bootstrap/cache uploads

exec apache2-foreground
