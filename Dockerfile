# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-progress --no-interaction --prefer-dist

COPY . .

RUN composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# On Railway entrypoint will change SERVER_NAME na ":$PORT"
ENV SERVER_NAME=:8080
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
