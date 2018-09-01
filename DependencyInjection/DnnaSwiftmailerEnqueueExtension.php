<?php

namespace Dnna\SwiftmailerEnqueueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DnnaSwiftmailerEnqueueExtension extends Extension
{
    /**
     * Loads the configuration.
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws InvalidConfigurationException
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('config.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $def = new Definition($container->getParameter('dnna_swiftmailer_enqueue.swiftmailer_spool.class'));
        $def->setPublic(false);
        $def->setArguments([
            new Reference($config['queue']['service_id']),
            $config['queue']['key']
        ]);
        $container->setDefinition('dnna_swiftmailer_enqueue.swiftmailer.spool', $def);
        $container->setAlias('swiftmailer.spool.enqueue', 'dnna_swiftmailer_enqueue.swiftmailer.spool');
    }
}
