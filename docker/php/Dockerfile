FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
