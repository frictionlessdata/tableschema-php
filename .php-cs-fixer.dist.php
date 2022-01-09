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
        '@PSR2' => true,
        '@Symfony' => true,
        // TODO Re-enable the follow rules once we have a passing github action
        'yoda_style' => false,
        'ordered_imports' => false,
        'visibility_required' => false,
    ])
    ->setFinder($finder)
;
return $config;
