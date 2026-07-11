<?php

$config = new PhpCsFixer\Config();
$config->getFinder()
    ->exclude('vendor')
    ->in(__DIR__)
    ->append([
        __FILE__,
    ]);
$config->setRules([
    '@PSR1' => true,
    '@Symfony' => true,
    'nullable_type_declaration' => [
        'syntax' => 'question_mark',
    ],
    'nullable_type_declaration_for_default_null_value' => true,
]);

return $config;
