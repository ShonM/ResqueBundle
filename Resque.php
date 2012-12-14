<?php

namespace ShonM\ResqueBundle;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Resque
{
    public function __construct($redis, ContainerInterface $container)
    {
        \Resque::setBackend($redis);
        \Resque_Event::listen('beforePerform', function(\Resque_Job $job) use ($container) {
            // TODO: will this have issues because of forking?  Would
            //       it be better to create a new container?
            // Easiest way to get new container is $kernel->shutdown();$kernel->boot(); but that may
            // be too heavy for our purposes
            $instance = $job->getInstance();

            if ($instance instanceof ContainerAwareInterface) {
                $instance->setContainer($container);
            }
        });
    }

    public function __call($func, $args)
    {
        return call_user_func_array(array('Resque', $func), $args);
    }

    public function add($job_name, $queue_name, $args = array())
    {

        if (strpos($queue_name, ':') !== false) {
            list($namespace, $queue_name) = explode(':', $queue_name);
            \Resque_Redis::prefix($namespace);
        }

        try {
            $klass = new \ReflectionClass($job_name);
            $jobId = \Resque::enqueue($queue_name, $klass->getName(), $args, true);

            return $jobId;
        } catch (\ReflectionException $rfe) {
            throw new \RuntimeException($rfe->getMessage());
        }
    }

    public function check($job_id, $namespace = false)
    {
        if ( ! empty($namespace)) {
            \Resque_Redis::prefix($namespace);
        }

        $status = new \Resque_Job_Status($job_id);
        if ( ! $status->isTracking()) {
            throw new \RuntimeException("Resque is not tracking the status of this job.\n");
        }

        $class = new \ReflectionObject($status);

        foreach ($class->getConstants() as $constant_name => $constant_value) {
            if ($constant_value == $status->get()) {
                break;
            }
        }

        return $status->get();
    }

    public function update($status, $to_job_id, $namespace)
    {
        if ( ! empty($namespace)) {
            \Resque_Redis::prefix($namespace);
        }

        $job = new \Resque_Job_Status($to_job_id);

        if ( ! $job->get()) {
            throw new \RuntimeException("Job {$to_job_id} was not found");
        }

        $class = new \ReflectionObject($job);

        foreach ($class->getConstants() as $constant_value) {
            if ($constant_value == $status) {
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

            if ($queue && ! preg_match('/' . $queue . '/', $worker->__toString())) {
                continue;
            }

            $workers[] = $worker;
        }

        return $workers;
    }

    public function worker($name = false)
    {
        $workers = $this->workers($name);

        if ($workers) {
            return reset($workers);
        }

        return false;
    }

    public function queues($name = false)
    {
        $queues = array();
        foreach (\Resque::queues() as $queue) {
            if ($queue == $name) {
                return $queue;
            }

            $queues[$queue] = $this->size($queue);
        }

        return $queues;
    }
}
