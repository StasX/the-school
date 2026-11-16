#!/bin/sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs storage/infection bootstrap/cache uploads
composer install --prefer-dist --no-interaction --no-progress
php artisan config:clear
php tests/prepare-database.php
if [ "${1:-}" = "infection" ]; then
    shift
    exec php -d pcov.enabled=1 vendor/bin/infection "$@"
fi

exec php artisan test --do-not-cache-result "$@"
