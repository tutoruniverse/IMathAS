FROM php:7.4.33-cli

WORKDIR /app
ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions gd opcache mcrypt memcache memcached exif redis pdo_mysql pcntl geoip igbinary
    
COPY ./ /app

CMD ["php", "-S", "0.0.0.0:8000"]