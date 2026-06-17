FROM node:20-alpine AS frontend
WORKDIR /app
COPY tmp_innerlink/package.json tmp_innerlink/package-lock.json ./
RUN npm ci
COPY tmp_innerlink/resources/ resources/
COPY tmp_innerlink/vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY tmp_innerlink/composer.json tmp_innerlink/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

FROM php:8.2-fpm-alpine AS runtime

RUN apk add --no-cache \
    postgresql-dev \
    nginx \
    supervisor

RUN docker-php-ext-install pdo_pgsql

COPY --from=vendor /app/vendor/ /app/vendor/
COPY --from=frontend /app/public/build/ /app/public/build/
COPY tmp_innerlink/ /app/

COPY app/Domains/ /app/app/Domains/
COPY database/migrations/ /database/migrations/

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh

WORKDIR /app

RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan package:discover --ansi && \
    php artisan view:cache

RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
