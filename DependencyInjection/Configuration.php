<?php

namespace Dnna\SwiftmailerEnqueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dnna_swiftmailer_enqueue');

        $rootNode
            ->children()
                ->arrayNode('queue')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service_id')
                            ->defaultValue('enqueue.transport.context')
                        ->end()
                        ->scalarNode('key')
                            ->defaultValue('swiftmailer_spool')
                        ->end()
                    ->end()
                ->end() // queue
            ->end()
        ;

        return $treeBuilder;
    }
}
