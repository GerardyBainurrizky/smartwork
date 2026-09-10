FROM php:8.3-cli-alpine

# Install dependensi dan ekstensi pdo_mysql
RUN apk add --no-cache nodejs npm git unzip libpng-dev libzip-dev \
    && docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install dependencies & build frontend
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}