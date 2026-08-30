# syntax=docker/dockerfile:1

FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

WORKDIR /var/www/html

# opcache is already compiled into the php:8.5 images, so it is not listed below.
RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        mysql-client \
        nodejs \
        npm \
        shadow \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && apk del .build-deps \
    && npm install --global corepack@latest \
    && corepack enable

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Align the container user with the host user so bind-mounted files stay writable.
RUN groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && chown -R www-data:www-data /var/www

COPY --chown=www-data:www-data . /var/www/html

# Created here so the anonymous node_modules volume inherits www-data ownership.
RUN mkdir -p /var/www/html/node_modules \
    && chown www-data:www-data /var/www/html/node_modules

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

RUN composer install --no-interaction --prefer-dist --no-scripts --no-progress \
    && composer dump-autoload --optimize

EXPOSE 9000 5173

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
