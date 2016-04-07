<?php

namespace ShonM\ResqueBundle;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Resque
{
    public $track;

    public function __construct($redis, ContainerInterface $container, $track = true, $namespace = 'resque')
    {
        \Resque::setBackend($redis);

        \Resque_Redis::prefix($namespace);

        \Resque_Event::listen('beforePerform', function(\Resque_Job $job) use ($container) {
            // TODO: will this have issues because of forking? Would it be better to create a new container?
            // Easiest way to get new container is $kernel->shutdown();$kernel->boot(); but that maybe too heavy for our purposes
            $instance = $job->getInstance();

            if ($instance instanceof ContainerAwareInterface) {
                $instance->setContainer($container);
            }
        });

        $this->track = (bool) $track;
    }

    public function __call($func, $args)
    {
        return call_user_func_array(array('Resque', $func), $args);
    }

    public function add($jobName, $queueName = 'default', $args = array())
    {

        if (strpos($queueName, ':') !== false) {
            list($namespace, $queueName) = explode(':', $queueName);
            \Resque_Redis::prefix($namespace);
        }

        $klass = new \ReflectionClass($jobName);

        try {
            \Resque::redis();

            try {
                $jobId = \Resque::enqueue($queueName, $klass->getName(), $args, $this->track);

                return $jobId;
            } catch (\ReflectionException $rfe) {
                throw new \RuntimeException($rfe->getMessage());
            }
        } catch (\CredisException $e) {
            if (strpos($e->getMessage(), 'Connection to Redis failed') !== false) {
                if (in_array('ShonM\ResqueBundle\Jobs\SynchronousInterface', class_implements($jobName))) {
                    $j = new \Resque_Job($queueName, array('class' => $klass->getName(), 'args' => array($args)));
                    return $j->perform();
                }
            }

            throw $e;
        }
    }

    public function check($jobId, $namespace = false)
    {
        if ( ! empty($namespace)) {
            \Resque_Redis::prefix($namespace);
        }

        $status = new \Resque_Job_Status($jobId);
        if ( ! $status->isTracking()) {
            throw new \RuntimeException("Resque is not tracking the status of this job.\n");
        }

        $class = new \ReflectionObject($status);

        foreach ($class->getConstants() as $constantValue) {
            if ($constantValue == $status->get()) {
                break;
            }
        }

        return $status->get();
    }

    public function update($status, $toJobId, $namespace)
    {
        if ( ! empty($namespace)) {
            \Resque_Redis::prefix($namespace);
        }

        $job = new \Resque_Job_Status($toJobId);

        if ( ! $job->get()) {
            throw new \RuntimeException("Job {$toJobId} was not found");
        }

        $class = new \ReflectionObject($job);

        foreach ($class->getConstants() as $constantValue) {
            if ($constantValue == $status) {
                $job->update($status);

                return true;
            }
        }

        return false;
    }

    public function workers($queue = false)
    {
        $workers = array();
        foreach (\Resque_Worker::all() as $worker) {
            $worker = new Worker($worker);

            if ($queue && ! preg_match('/\\' . $queue . '/', $worker->__toString())) {
                continue;
            }

            $workers[] = $worker;
        }

        return $workers;
    }

    public function worker($name = false)
    {
        $workers = $this->workers();

        foreach ($workers as $worker) {
            if ($worker->getId() === $name) {
                return $worker;
            }
        }

        return false;
    }

    public function queues($name = false)
    {
        $queues = array();
        foreach (\Resque::queues() as $id) {
            $queue = new Queue($id);

            if ($id === $name) {
                return $queue;
            }

            $queues[$id] = $queue;
        }

        return $queues;
    }

    public function stat($name)
    {
        return \Resque_Stat::get($name);
    }
}
