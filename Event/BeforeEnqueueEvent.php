<?php

namespace ShonM\ResqueBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class BeforeEnqueueEvent extends Event
{
    protected $class;

    protected $arguments;

    protected $queue;

    public function __construct($class, $arguments, $queue)
    {
        $this->class = $class;
        $this->arguments = $arguments;
        $this->queue = $queue;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getQueue()
    {
        return $this->queue;
    }
}
