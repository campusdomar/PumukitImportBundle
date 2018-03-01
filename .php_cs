<?php

$config = PhpCsFixer\Config::create()->setRules(
    array(
        '@Symfony' => true,
    )
)->setFinder(
    PhpCsFixer\Finder::create()->exclude('vendor')->in(__DIR__)
);

return $config;