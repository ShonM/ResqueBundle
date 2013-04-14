<?php

namespace ShonM\ResqueBundle;

use Resque\Worker as BaseWorker;

class Worker extends BaseWorker
{
    private $forkCount = 1;

    private $job;

    public function forkInstances($count)
    {
        settype($count, 'int');

        $this->forkCount = 1;
        if ($count > 1) {
            if (function_exists('pcntl_fork')) {
                $this->forkCount = $count;
            } else {
                fwrite(STDOUT, "*** Fork could not be initialized. PHP function pcntl_fork() does exist\n");
            }
        }
    }

    public function getForkInstances()
    {
        return $this->forkCount;
    }

    public function daemonize()
    {
        if (function_exists('pcntl_fork')) {
            for ($i = 0; $i < $this->getForkInstances(); ++$i) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    throw new \RuntimeException("Could not fork worker {$i}");
                } elseif (! $pid) {
                    $this->work($this->getInterval());
                    die;
                }
            }
        } else {
            $this->work($this->getInterval());
        }
    }

    public function job()
    {
        if ($this->job === null) {
            $this->job = $this->job();
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
