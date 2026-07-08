FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

# Instalar dependências do sistema
RUN apk add --no-cache \
    curl \
    openssl \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    sockets \
    && pecl install redis \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${GID} appuser && adduser -u ${UID} -G appuser -D appuser

WORKDIR /var/www

# Copiar arquivos de dependência primeiro (cache de layers)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-dev

# Copiar restante do projeto
COPY . .

# Finalizar autoload
RUN composer dump-autoload --optimize

# Permissões de storage
RUN chown -R appuser:appuser /var/www

# Entrypoint para corrigir permissões em volumes montados
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Desabilitar OPcache em dev (volume mount + opcache = rotas stale)
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/opcache-off.ini

# Configurar php-fpm para rodar como appuser
RUN sed -i "s/user = www-data/user = appuser/" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = appuser/" /usr/local/etc/php-fpm.d/www.conf

USER appuser

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
