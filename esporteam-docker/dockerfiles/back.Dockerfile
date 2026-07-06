# Imagem dev para esporteam-back (backend Laravel)
FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libpq \
        oniguruma-dev \
        linux-headers \
        git \
        unzip \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql bcmath mbstring \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Container roda como root pra simplificar permissão de volume montado em dev.

WORKDIR /var/www

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
