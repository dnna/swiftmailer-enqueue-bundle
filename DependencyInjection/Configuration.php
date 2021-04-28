<?php

namespace Dnna\SwiftmailerEnqueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dnna_swiftmailer_enqueue');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('queue')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service_id')
                            ->defaultValue('enqueue.transport.default.context')
                            ->info('Enqueue transport service id. Usually the default enqueue.transport.context is OK')
                        ->end()
                        ->scalarNode('key')
                            ->defaultValue('swiftmailer_spool')
                            ->info('Name of the queue that will be used as the spool')
                        ->end()
                        ->booleanNode('requeue_on_exception')
                            ->defaultValue(false)
                            ->info('If the email sending fails with an exception requeue the email to be retried')
                        ->end()
                        ->integerNode('max_requeue_attempts')
                            ->defaultValue(5)
                            ->info('How many times to retry sending if requeue_on_exception is set to true')
                        ->end()
                    ->end()
                ->end() // queue
                ->arrayNode('consumption')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('receive_timeout')
                            ->defaultValue(1000)
                            ->info('How often to poll the queue for new messages')
                        ->end()
                    ->end()
                ->end() // consumption
                ->arrayNode('extensions')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('signal_extension')
                            ->defaultValue(function_exists('pcntl_signal_dispatch'))
                            ->info('This extension enables graceful termination of any running spool send commands')
                        ->end()
                    ->end()
                ->end() // extensions
            ->end()
        ;

        return $treeBuilder;
    }
}
