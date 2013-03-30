<?php

namespace ShonM\ResqueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\Loader;

class ShonMResqueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // Check for 4 config options that MUST be set
        if (! isset($config['host'])) {
            throw new \InvalidArgumentException('The "host" option must be set');
        }
        if (! isset($config['port'])) {
            throw new \InvalidArgumentException('The "port" option must be set');
        }
        if (! isset($config['password'])) {
            throw new \InvalidArgumentException('The "password" option must be set');
        }
        if (! isset($config['track'])) {
            throw new \InvalidArgumentException('The "track" option must be set');
        }

        // Move those config options into parameters (for service injection)
        $container->setParameter('resque.host', $config['host']);
        $container->setParameter('resque.port', $config['port']);
        $container->setParameter('resque.password', $config['password']);
        $container->setParameter('resque.track', $config['track']);

        if (! empty($config['strategies'])) {
            foreach ($config['strategies'] as $strategy => $configuration) {
                $container->setParameter('resque.strategies.' . $strategy, $configuration);
            }
        }
    }
}
