FROM php:8.3-cli-alpine

# Install dependensi sistem, library gambar (GD), zip, dan sqlite-dev
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

# Install dependensi PHP & Build asset frontend
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build

# Siapkan file database sqlite kosong & atur permissions
RUN mkdir -p database \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

EXPOSE 8080

CMD sh -c "export APP_KEY='[base64:j726Ob5iq8nYmZWOt2NoEzmf0qUbSRX+IB0dj+I/kBA=]' && export DB_CONNECTION=mysql && export DB_HOST='[gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com]' && export DB_PORT=4000 && export DB_DATABASE=test && export DB_USERNAME='[2QZY7FhowMojDvc.root]' && export DB_PASSWORD='[Lvy41bFvwNOQ8v2C]' && (php artisan migrate --force &) && mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache && touch storage/logs/laravel.log && (tail -n 0 -F storage/logs/laravel.log &) && php -S 0.0.0.0:${PORT:-8080} -t public"