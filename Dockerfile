FROM php:8.3-cli-alpine

# Install dependensi sistem, library gambar (GD), dan zip
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Izinkan composer dijalankan sebagai root di container
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

# Install dependensi PHP & Build asset frontend
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}