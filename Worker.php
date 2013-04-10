<?php

namespace ShonM\ResqueBundle;

use Resque\Worker as BaseWorker;

class Worker extends BaseWorker
{
    protected $job = null;

    public function job()
    {
        if ($this->job === null) {
            $this->job = $this->worker->job();
        }

        return $this->job;
    }

    public function getQueues()
    {
        return array_map(function ($queue) {
            return new Queue($queue);
        }, $this->queues());
    }
}
