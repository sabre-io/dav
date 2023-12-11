<?php

$config = new PhpCsFixer\Config();
$config->getFinder()
    ->exclude('vendor')
    ->in(__DIR__);
$config->setRules([
    '@PSR1' => true,
    '@Symfony' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true]
]);

return $config;