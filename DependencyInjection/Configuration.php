<?php

namespace Pumukit\ImportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_import');

        $rootNode
          ->children()
            ->booleanNode('ignore_arca')
              ->defaultValue(true)
              ->info('Ignore ARCA publication channel when importing.')
            ->end()
            ->booleanNode('ignore_google')
              ->defaultValue(true)
              ->info('Ignore GoogleVideoSiteMap publication channel when importing.')
            ->end()
            ->booleanNode('ignore_itunesu')
              ->defaultValue(true)
              ->info('Ignore iTunesU publication channel when importing.')
            ->end()
            ->booleanNode('ignore_youtube')
              ->defaultValue(true)
              ->info('Ignore YouTubeEDU publication channel when importing.')
            ->end()
          ->end();

        return $treeBuilder;
    }
}
