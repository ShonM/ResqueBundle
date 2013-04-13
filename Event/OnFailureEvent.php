<?php

namespace ShonM\ResqueBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Resque\Job;

class OnFailureEvent extends Event
{
    protected $exception;

    protected $job;

    public function __construct(\Exception $exception, Job $job)
    {
        $this->exception = $exception;
        $this->job = $job;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getJob()
    {
        return $this->job;
    }
}
