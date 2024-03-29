<?php

/**
 * code style configuration
 * you may additionally create .php_cs file which will extend the configuration in this file
 */

$finder = new PhpCsFixer\Finder();
$config = new PhpCsFixer\Config('tableschema-php', 'tableschema-php style guide');
$finder
    ->exclude("tests/fixtures")
    ->exclude("src/schemas")
    ->in(__DIR__)
;
$config
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
return $config;
