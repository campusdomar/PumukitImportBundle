<?php

$config = PhpCsFixer\Config::create()->setRules(
    array(
        '@Symfony' => true,
    )
)->setFinder(
    PhpCsFixer\Finder::create()->exclude('vendor')->in(__DIR__)
);

// special handling of fabbot.io service if it's using too old PHP CS Fixer version
try {
    PhpCsFixer\FixerFactory::create()->registerBuiltInFixers()->registerCustomFixers(
            $config->getCustomFixers()
        )->useRuleSet(new PhpCsFixer\RuleSet($config->getRules()));
} catch (PhpCsFixer\ConfigurationException\InvalidConfigurationException $e) {
    $config->setRules([]);
} catch (UnexpectedValueException $e) {
    $config->setRules([]);
} catch (InvalidArgumentException $e) {
    $config->setRules([]);
}

return $config;
