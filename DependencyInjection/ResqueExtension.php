<?php

namespace ShonM\ResqueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\Loader;

class ResqueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (!isset($config['host'])) {
            throw new \InvalidArgumentException('The "host" option must be set');
        }

        if (!isset($config['port'])) {
            throw new \InvalidArgumentException('The "port" option must be set');
        }

        if (!isset($config['password'])) {
            throw new \InvalidArgumentException('The "password" option must be set');
        }

        $container->setParameter('resque.host', $config['host']);
        $container->setParameter('resque.port', $config['port']);
        $container->setParameter('resque.password', $config['password']);
    }

    public function getAlias()
    {
        return 'resque';
    }
}
