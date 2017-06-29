#!/usr/bin/env bash

if [ ! -f ./php-cs-fixer ]; then
    wget https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v2.3.2/php-cs-fixer.phar -O php-cs-fixer
    chmod +x php-cs-fixer
fi
