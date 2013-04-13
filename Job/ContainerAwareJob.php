<?php

namespace ShonM\ResqueBundle\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface,
    Symfony\Component\DependencyInjection\ContainerInterface,
    ShonM\ResqueBundle\JobInterface;

abstract class ContainerAwareJob implements JobInterface, ContainerAwareInterface
{
    /**
     * @var \Resque\Job
     */
    public $job;

    /**
     * @var mixed
     */
    public $args;

    /**
     * @var string
     */
    public $queue;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer (ContainerInterface $container = null)
    {
        $this->container = $container;

        return $this;
    }

    public function getContainer ()
    {
        return $this->container;
    }
}
