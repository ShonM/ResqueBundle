<?php

namespace ShonM\ResqueBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    ShonM\ResqueBundle\DependencyInjection\ResqueExtension;

class ShonMResqueBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->registerExtension(new ResqueExtension());
    }
}
