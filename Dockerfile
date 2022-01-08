FROM php:7.4-cli

# system dependecies
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-install -j$(nproc) gd
## PHP dependencies
RUN docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug
# composer
RUN curl -sS https://getcomposer.org/installer | php \
	  && mv composer.phar /usr/local/bin/composer \
	  && apt-get install git unzip -y
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV XDEBUG_MODE=coverage
WORKDIR /src