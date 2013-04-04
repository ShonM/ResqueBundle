<?php

namespace ShonM\ResqueBundle;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Resque
{
    public $track;

    public function __construct($redis, ContainerInterface $container, $track = true)
    {
        \Resque::setBackend($redis);

        // Forking means this container will become "stale" and workers must be restarted to get a new one
        \Resque_Event::listen('beforePerform', function(\Resque_Job $job) use ($container) {
            $instance = $job->getInstance();

            if ($instance instanceof ContainerAwareInterface) {
                $instance->setContainer($container);
            }
        });

        // The most fantastic thing ever - since Redis doesn't allow delete-by-index on lists (because they're actually deque, or "double ended queue"s)
        // We basically double its output, moving all failures to their own keys as well, which we can operate on afterwards
        // Actually, the failure list is not even worth maintaining, so let's hope that goes away soon.
        $that = $this;
        \Resque_Event::listen('onFailure', function(\Exception $exception, \Resque_Job $job) use ($that) {
            $data = new \stdClass;
            $data->failed_at = strftime('%a %b %d %H:%M:%S %Z %Y');
            $data->payload = $job->payload;
            $data->exception = get_class($exception);
            $data->error = $exception->getMessage();
            $data->backtrace = explode("\n", $exception->getTraceAsString());
            $data->worker = (string) $job->worker;
            $data->queue = $job->queue;
            $data = json_encode($data);

            $id = 'failed:' . $job->payload['id'];
            $that->redis()->set($id, $data);
            $that->redis()->expire($id, 86400); // Expires after 24h
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
