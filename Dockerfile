FROM php:7.4-fpm-alpine

RUN apk add --update \
    && docker-php-ext-install pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /app

RUN cd "/app" && cp .env.example .env && composer install

WORKDIR /app

EXPOSE 8001
CMD ["sh", "-c", "php artisan key:generate && php -S 0.0.0.0:8001 -t public"]
