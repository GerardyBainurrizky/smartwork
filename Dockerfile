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

CMD sh -c "php -S 0.0.0.0:${PORT:-8080} -t public"