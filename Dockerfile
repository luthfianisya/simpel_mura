FROM php:8.3-cli

# Ekstensi PHP yang dibutuhkan: pdo_mysql (database), mbstring (Laravel inti),
# zip+gd (PhpSpreadsheet baca/tulis .xlsx), gd (dompdf render gambar di PDF).
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && chmod -R 775 storage bootstrap/cache

# Render (dan platform serupa) inject PORT saat runtime — default 10000 dipakai
# kalau dijalankan lokal/tanpa variabel itu.
EXPOSE 10000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
