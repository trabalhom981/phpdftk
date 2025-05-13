FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    pdftk \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app
