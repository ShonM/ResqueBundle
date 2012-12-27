<?php

namespace ShonM\ResqueBundle\Tests\Functional;

call_user_func(function() {
    if ( ! is_file($autoloadFile = __DIR__.'/../../vendor/autoload.php')) {
        throw new \LogicException('The autoload file "vendor/autoload.php" was not found. Did you run "composer install --dev"?');
    }

    require_once $autoloadFile;
});

use Symfony\Component\Filesystem\Filesystem,
    Symfony\Component\Config\Loader\LoaderInterface,
    Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config = 'test')
    {
        parent::__construct('test', true);

        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        $config .= '.yml';

        if ( ! is_file($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),

            new \ShonM\ResqueBundle\Tests\Functional\TestBundle\TestBundle(),
            new \ShonM\ResqueBundle\ShonMResqueBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/ShonMResqueBundle/'.substr(sha1($this->config), 0, 6).'/cache';
    }

    protected function getContainerClass()
    {
        return parent::getContainerClass().'_'.substr(sha1($this->config), 0, 6);
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/ShonMResqueBundle/'.substr(sha1($this->config), 0, 6).'/logs';
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($config)
    {
        $this->__construct($config);
    }
}
