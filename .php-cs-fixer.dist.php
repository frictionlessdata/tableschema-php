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
        'no_break_comment' => false,
        'phpdoc_types_order' => false,
        'single_line_throw' => false,
        'no_trailing_comma_in_singleline_array' => false,
        'trim_array_spaces' => false
    ])
    ->setFinder($finder)
;
return $config;
