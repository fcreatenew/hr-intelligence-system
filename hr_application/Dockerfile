# Production image untuk hr_application (Laravel).
#
# Tahap build memasang dependensi Composer (tanpa dev) dan menyiapkan aplikasi.
# Tahap runtime hanya membawa yang diperlukan agar image kecil dan tidak
# membawa toolchain build.
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction


FROM php:8.3-cli AS runtime

# Ekstensi yang dibutuhkan Laravel + SQLite (default DB_CONNECTION=sqlite).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev unzip sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chmod -R 775 storage bootstrap/cache database

EXPOSE 8080

# Migrasi dijalankan saat start agar container langsung siap dipakai.
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080"]
