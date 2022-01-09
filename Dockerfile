FROM php:8.0-cli

RUN apt-get update

## PHP dependencies
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
# composer
RUN curl -sS https://getcomposer.org/installer | php \
	  && mv composer.phar /usr/local/bin/composer \
	  && apt-get install git unzip -y
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV XDEBUG_MODE=coverage
WORKDIR /src