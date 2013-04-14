<?php

namespace ShonM\ResqueBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use ShonM\ResqueBundle\Event\BeforePerformEvent;

class ContainerAwareListener
{
    protected $container;

    // TODO: SM: This should be typehinted, but "functional" tests throw a fit
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onBeforePerform(BeforePerformEvent $event)
    {
        $instance = $event->getJob()->getInstance();

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }
    }
}