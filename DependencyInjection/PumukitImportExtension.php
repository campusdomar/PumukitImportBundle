<?php

namespace Pumukit\ImportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitImportExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_import.ignore', $config);
        $container->setParameter('pumukit_import.ignore_arca', $config['ignore_arca']);
        $container->setParameter('pumukit_import.ignore_google', $config['ignore_google']);
        $container->setParameter('pumukit_import.ignore_itunesu', $config['ignore_itunesu']);
        $container->setParameter('pumukit_import.ignore_youtube', $config['ignore_youtube']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
