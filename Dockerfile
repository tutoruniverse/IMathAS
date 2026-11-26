# PHP 8.4.27+ fixes CVE-2026-109618 / CVE-2026-109619 (php.net security advisory 2026-10-21)
FROM php:8.4.27-fpm-alpine

RUN curl -sSLf \
    -o /usr/local/bin/install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions gd opcache exif pdo_mysql pcntl igbinary redis memcache memcached

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/
COPY ./docker/php/www.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

COPY ./ /app
