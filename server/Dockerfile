FROM node:26.5.0-alpine AS frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build


FROM composer:2.10 AS backend-builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts

COPY . .

RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative


FROM php:8.5.12-apache

RUN pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html

COPY --from=backend-builder /app .
COPY --from=frontend-builder /app/public/build ./public/build

COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
RUN chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        uploads \
    && chmod -R 775 \
        storage \
        bootstrap/cache \
        uploads

RUN sed -ri \
    -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

USER www-data
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl --fail http://localhost/api/health || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
