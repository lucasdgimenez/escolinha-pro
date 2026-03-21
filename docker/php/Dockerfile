FROM php:8.3-fpm-alpine3.20

RUN sed -i 's|https://dl-cdn.alpinelinux.org|http://dl-cdn.alpinelinux.org|g' /etc/apk/repositories

RUN apk add --no-cache \
    bash git curl zip unzip supervisor \
    libpng-dev oniguruma-dev libxml2-dev libzip-dev \
    postgresql-dev freetype-dev libjpeg-turbo-dev libwebp-dev \
    pcre-dev $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
