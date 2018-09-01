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
                    ->children()
                        ->scalarNode('service_id')->end()
                        ->scalarNode('key')->end()
                    ->end()
                ->end() // queue
            ->end()
        ;

        return $treeBuilder;
    }
}
