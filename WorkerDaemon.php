<?php

namespace ShonM\ResqueBundle;

class WorkerDaemon
{
    private $redis;
    private $password;
    private $queue = '*';
    private $logging = 'normal';
    private $checker_interval = 5;
    private $fork_count = 1;

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
        $this->checker_interval = (int) $interval;
    }

    public function forkInstances($count)
    {
        settype($count, 'int');

        if ($count > 1) {
            if (function_exists('pcntl_fork')) {
                $this->fork_count = $count;
            } else {
                fwrite(STDOUT, "*** Fork could not initialized. PHP function pcntl_fork() does NOT exists \n");
                $this->fork_count = 1;
            }
        } else {
            $this->fork_count = 1;
        }
    }

    public function getForkInstances()
    {
        return $this->fork_count;
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

    private function work()
    {
        $worker = new \Resque_Worker(explode(',', $this->queue));
        $worker->logLevel = $this->loglevel();
        fwrite(STDOUT, '*** Starting worker: ' . $worker . "\n");
        $worker->work($this->checker_interval);
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
