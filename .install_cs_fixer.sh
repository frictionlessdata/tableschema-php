#!/usr/bin/env bash

if [ ! -f ./php-cs-fixer ]; then
    wget https://cs.symfony.com/download/php-cs-fixer-v3.phar -O php-cs-fixer
    chmod +x php-cs-fixer
fi
