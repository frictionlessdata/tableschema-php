#!/usr/bin/env bash

if [ ! -f ./php-cs-fixer ]; then
    wget https://cs.symfony.com/download/v3.4.0/php-cs-fixer.phar -O php-cs-fixer
    chmod +x php-cs-fixer
fi
