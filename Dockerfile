FROM php:7.3-cli

RUN apt-get update
RUN pecl install xdebug \
        && docker-php-ext-enable xdebug
RUN curl -sS https://getcomposer.org/installer | php \
	  && mv composer.phar /usr/local/bin/composer \
	  && apt-get install -y git unzip
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /src