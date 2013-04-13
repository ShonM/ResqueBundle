<?php

namespace ShonM\ResqueBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Resque\Job;

class BeforePerformEvent extends Event
{
    protected $container;

    protected $job;

    // FIXME: SM: Typehint with ContainerAwareInterface breaks the tests:
    //     failed: Argument 1 passed to ShonM\ResqueBundle\Event\BeforePerformEvent::__construct() must implement interface
    //     Symfony\Component\DependencyInjection\ContainerAwareInterface, instance of FunctionalTestDebugProjectContainer_a5e72c given
    public function __construct($container, Job $job)
    {
        $this->container = $container;
        $this->job = $job;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getJob()
    {
        return $this->job;
    }
}
