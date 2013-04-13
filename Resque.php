<?php

namespace ShonM\ResqueBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Resque\Resque as BaseResque;
use Resque\Redis;
use Resque\Event;
use Resque\Stat;
use Resque\Job;
use Resque\Job\Status;
use Resque\Worker;

class Resque extends BaseResque
{
    public $track;
    private $container;

    public function __construct($redis, ContainerInterface $container, $track = true)
    {
        $this->container = $container;

        parent::setBackend($redis);

        // Forking means this container will become "stale" and workers must be restarted to get a new one
        Event::listen('beforePerform', function(Job $job) use ($container) {
            $instance = $job->getInstance();

            if ($instance instanceof ContainerAwareInterface) {
                $instance->setContainer($container);
            }
        });

        $this->track = (bool) $track;
    }

    public function add($jobName, $queueName = 'default', $args = array())
    {
        if (false !== $pos = strpos($jobName, ':')) {
            $bundle = $this->container->get('kernel')->getBundle(substr($jobName, 0, $pos));
            $jobName = $bundle->getNamespace().'\\Job\\'.substr($jobName, $pos + 1).'Job';
        }

        if (strpos($queueName, ':') !== false) {
            list($namespace, $queueName) = explode(':', $queueName);
            Redis::prefix($namespace);
        }

        $class = new \ReflectionClass($jobName);

        try {
            parent::redis();

            try {
                $jobId = parent::enqueue($queueName, $class->getName(), $args, $this->track);

                return $jobId;
            } catch (\ReflectionException $rfe) {
                throw new \RuntimeException($rfe->getMessage());
            }
        } catch (\CredisException $e) {
            if (strpos($e->getMessage(), 'Connection to Redis failed') !== false) {
                if ($class->implementsInterface('ShonM\ResqueBundle\Job\SynchronousInterface')) {
                    $j = new Job($queueName, array('class' => $class->getName(), 'args' => array($args)));

                    return $j->perform();
                }
            }

            throw $e;
        }
    }

    public function check($jobId, $namespace = false)
    {
        if ( ! empty($namespace)) {
            Redis::prefix($namespace);
        }

        $status = new Status($jobId);
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
            Redis::prefix($namespace);
        }

        $job = new Status($toJobId);

        if ( ! $job->get()) {
            throw new \RuntimeException('Job ' . $toJobId . ' was not found');
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
        foreach (Worker::all() as $worker) {
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
            if ($worker === $name) {
                return $worker;
            }
        }

        return false;
    }

    public function getQueues($name = false)
    {
        $queues = array();
        foreach (parent::queues() as $id) {
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
        return Stat::get($name);
    }
}
