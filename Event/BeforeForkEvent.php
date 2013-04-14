<?php

namespace ShonM\ResqueBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Resque\Job;

class BeforeForkEvent extends Event
{
    protected $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }
}
