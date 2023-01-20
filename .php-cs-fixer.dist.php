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
]);

return $config;
