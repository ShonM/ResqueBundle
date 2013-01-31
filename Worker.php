<?php

namespace ShonM\ResqueBundle;

class Worker
{
    protected $job = null;

    public function __construct(\Resque_Worker $worker)
    {
        $this->worker = $worker;
    }

    public function __call($func, $args)
    {
        return call_user_func_array(array($this->worker, $func), $args);
    }

    public function job()
    {
        if ($this->job === null) {
            $this->job = $this->worker->job();
        }

        return $this->job;
    }

    public function __toString()
    {
        return $this->worker->__toString();
    }

    public function getId()
    {
        return (string) $this->worker;
    }

    public function getQueues()
    {
        return array_map(function ($queue) {
            return new Queue($queue);
        }, $this->worker->queues());
    }
}
