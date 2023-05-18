<?php

require __DIR__ . '/vendor/autoload.php';

$header = <<<'HEADER'
This file is part of the Drewlabs package.

(c) Sidoine Azandrew <azandrewdevelopper@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$rules = [
    '@PHP56Migration:risky' => true,
    "@PHP81Migration" => true,
    "@PHP74Migration" => true,
    '@PHP70Migration' => true,
    '@PHP70Migration:risky' => true,
    '@PHP71Migration' => true,
    '@PHP71Migration:risky' => true,
    '@PHPUnit57Migration:risky' => true,
    '@PHPUnit60Migration:risky' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'compact_nullable_typehint' => true,
    'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
    'header_comment' => [
        'header' => $header,
    ],
    'list_syntax' => ['syntax' => 'short'],
    'logical_operators' => true,
    'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    'no_extra_blank_lines' => true,
    'no_php4_constructor' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    'no_useless_else' => true,
    'no_useless_return' => true,
    'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => false],
    'ordered_class_elements' => true,
    'ordered_imports' => true,
    'php_unit_method_casing' => false,
    'php_unit_set_up_tear_down_visibility' => true,
    'php_unit_strict' => true,
    'php_unit_test_annotation' => false,
    'phpdoc_order' => true,
    'single_line_throw' => false,
    'static_lambda' => true,
    'strict_comparison' => true,
    'strict_param' => true,
    'void_return' => false,
];


$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('Tests/Fixtures')
    ->exclude('tests/Fixtures')
    ->exclude('Resources/skeleton')
    ->exclude('Resources/public/vendor');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true);
