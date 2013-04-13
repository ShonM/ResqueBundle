<?php

namespace ShonM\ResqueBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use ShonM\ResqueBundle\Event\BeforePerformEvent;

class ContainerAwareListener
{
    public function onBeforePerform(BeforePerformEvent $event)
    {
        $instance = $event->getJob()->getInstance();

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }
    }
}