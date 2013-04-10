<?php

namespace ShonM\ResqueBundle;

use Resque\Resque as BaseResque;
use Resque\Redis;
use ShonM\ResqueBundle\Worker;
use Resque\Job\Strategy\StrategyInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;


class WorkerDaemon
{
    private $redis;
    private $password;
    private $queue = '*';
    private $logging = 'normal';
    private $checkerInterval = 5;
    private $forkCount = 1;
    private $jobStrategy;
    private $logger;

    public function __construct($redis, $password = false, LoggerInterface $logger = null)
    {
        $this->redis = $redis;
        $this->password = $password;
        $this->logger = $logger;
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
                return Worker::LOG_DEBUG;
            case 'normal' :
                return Worker::LOG_INFO;
            default :
                return Worker::LOG_INFO;
        }
    }

    public function setJobStrategy(StrategyInterface $jobStrategy)
    {
        $this->jobStrategy = $jobStrategy;

        return $this;
    }

    public function work()
    {
        BaseResque::setBackend($this->redis);

        if ($this->password) {
            BaseResque::redis()->auth($this->password);
        }

        fwrite(STDOUT, '*** Connecting to ' . (($this->password) ? $this->password . '@' : '') . $this->redis . "\n");

        if (strpos($this->queue, ':') !== false) {
            list($namespace, $queue) = explode(':', $this->queue);
            Redis::prefix($namespace);
            $this->queue = $queue;
        }

        $worker = new Worker(explode(',', $this->queue), $this->logger);
        $worker->logLevel = $this->loglevel();

        if ($this->jobStrategy) {
            $worker->setJobStrategy($this->jobStrategy);
        }

        fwrite(STDOUT, '*** Starting worker: ' . $worker . "\n");
        $worker->work($this->checkerInterval);
    }

    public function daemonize()
    {
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
