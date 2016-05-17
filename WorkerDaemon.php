<?php

namespace ShonM\ResqueBundle;

use Resque_JobStrategy_Interface;

class WorkerDaemon
{
    private $redis;
    private $password;
    private $queue = '*';
    private $logging = 'normal';
    private $checkerInterval = 5;
    private $blocking = false;
    private $forkCount = 1;
    private $jobStrategy;

    public function __construct($redis, $password = false)
    {
        $this->redis = $redis;
        $this->password = $password;
    }

    public function defineQueue($name)
    {
        $this->queue = $name;
    }

    public function verbose($mode)
    {
        $this->logging = $mode;
    }

    public function setInterval($interval)
    {
        $this->checkerInterval = (int) $interval;
    }

    public function setBlocking($blocking)
    {
        $this->blocking = $blocking;
    }

    public function forkInstances($count)
    {
        settype($count, 'int');

        if ($count > 1) {
            if (function_exists('pcntl_fork')) {
                $this->forkCount = $count;
            } else {
                fwrite(STDOUT, "*** Fork could not initialized. PHP function pcntl_fork() does NOT exists \n");
                $this->forkCount = 1;
            }
        } else {
            $this->forkCount = 1;
        }
    }

    public function getForkInstances()
    {
        return $this->forkCount;
    }

    private function loglevel()
    {
        switch ($this->logging) {
            case 'verbose' :
                return \Resque_Worker::LOG_VERBOSE;
            case 'normal' :
                return \Resque_Worker::LOG_NONE;
            default :
                return \Resque_Worker::LOG_NONE;
        }
    }

    public function setJobStrategy(Resque_JobStrategy_Interface $jobStrategy)
    {
        $this->jobStrategy = $jobStrategy;

        return $this;
    }

    private function work()
    {
        $worker = new \Resque_Worker(explode(',', $this->queue));
        $worker->logLevel = $this->loglevel();

        if ($this->jobStrategy) {
            $worker->setJobStrategy($this->jobStrategy);
        }

        fwrite(STDOUT, '*** Starting worker: ' . $worker . "\n");
        $worker->work($this->checkerInterval, $this->blocking);
    }

    public function daemonize()
    {
        \Resque::setBackend($this->redis);

        if ($this->password) {
            \Resque::redis()->auth($this->password);
        }

        fwrite(STDOUT, '*** Connecting to ' . (($this->password) ? $this->password . '@' : '') . $this->redis . "\n");

        if (strpos($this->queue, ':') !== false) {
            list($namespace, $queue) = explode(':', $this->queue);
            \Resque_Redis::prefix($namespace);
            $this->queue = $queue;
        }

        if (function_exists('pcntl_fork')) {
            for ($i = 0; $i < $this->getForkInstances(); ++$i) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    throw new \RuntimeException("Could not fork worker {$i}");
                } elseif (! $pid) {
                    $this->work();
                    die();
                }
            }
        } else {
            $this->work();
        }
    }
}
