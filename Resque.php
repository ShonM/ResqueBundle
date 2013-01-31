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
            // TODO: will this have issues because of forking? Would it be better to create a new container?
            // Easiest way to get new container is $kernel->shutdown();$kernel->boot(); but that maybe too heavy for our purposes
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

            $that->redis()->set('failed:' . $job->payload['id'], $data);
        });
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

        try {
            $klass = new \ReflectionClass($jobName);
            $jobId = \Resque::enqueue($queueName, $klass->getName(), $args, true);

            return $jobId;
        } catch (\ReflectionException $rfe) {
            throw new \RuntimeException($rfe->getMessage());
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
        foreach (\Resque::queues() as $queue) {
            if ($queue == $name) {
                return $queue;
            }

            $queues[$queue] = $this->size($queue);
        }

        return $queues;
    }
}
