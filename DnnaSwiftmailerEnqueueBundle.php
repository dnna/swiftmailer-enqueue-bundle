<?php

namespace Dnna\SwiftmailerEnqueueBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
/**
 * DnnaSwiftmailerEnqueueBundle
 */
class DnnaSwiftmailerEnqueueBundle extends Bundle
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
