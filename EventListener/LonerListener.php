<?php

namespace ShonM\ResqueBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ShonM\ResqueBundle\Resque;
use ShonM\ResqueBundle\Annotation\Loner;
use ShonM\ResqueBundle\Exception\LonerException;
use Resque\Job;
use ReflectionClass;

class LonerListener
{
    protected $resque;

    protected $annotationReader;

    public function __construct(Resque $resque, Reader $annotationReader)
    {
        $this->resque = $resque;
        $this->annotationReader = $annotationReader;

        AnnotationRegistry::registerFile(__DIR__ . '/../Annotation/Loner.php');
    }

    public function onBeforeEnqueue($event)
    {
        $queue = $event->getQueue();
        $class = $event->getClass();
        $item = array(
            'class' => $class,
            'args'  => $event->getArguments(),
        );

        if (!$this->getLonerAnnotation($class)) {
            return;
        }

        $ttl = $this->isLonerQueued($queue, $item);
        if ($ttl > 0) {
            throw new LonerException("'$class' is already queued, and will expire in $ttl seconds.");
        } else {
            $this->markLonerAsQueued($queue, $item);
        }
    }

    public function onDone($event)
    {
        $this->markLonerAsUnqueued($event->getJob());
    }

    protected function getLonerAnnotation($class)
    {
        return $this->annotationReader->getClassAnnotation(
            new ReflectionClass($class),
            'ShonM\ResqueBundle\Annotation\Loner'
        );
    }

    protected function isLonerQueued($queue, $item)
    {
        $loner = $this->getLonerAnnotation($item['class']);
        $key = $this->lonerKey($queue, $item, $loner);

        return $this->resque->redis()->ttl($key);
    }

    protected function markLonerAsQueued($queue, $item)
    {
        $loner = $this->getLonerAnnotation($item['class']);
        if (! $loner) {
            return;
        }

        $key = $this->lonerKey($queue, $item, $loner);
        if ($loner->ttl == -1) {
            $this->resque->redis()->set($key, true);
        } else {
            $this->resque->redis()->set($key, true, $loner->ttl);
        }
    }

    protected function markLonerAsUnqueued($job)
    {
        $item = $job instanceof Job ? (array) $job->payload : $job;
        $item['args'] = reset($item['args']);
        $loner = $this->getLonerAnnotation($item['class']);

        if ($loner) {
            $key = $this->lonerKey($job->queue, $item, $loner);
            $this->resque->redis()->del($key);
        }
    }

    protected function jobDestroy($queue, $class, $args)
    {
        $redisQueue = "queue:$queue";
        foreach ($this->resque->redis()->lrange($redisQueue, 0, -1) as $string) {
            $decoded = json_decode($string);

            if ($decoded['class'] == $class && (empty($args) || $decoded['args'] == $args)) {
                $this->markLonerAsUnqueued($queue, $decoded);
            }
        }
    }

    protected function cleanupLoners($queue)
    {
        $keys = $this->resque->redis()->keys("loners:queue:$queue:job:*");
        if ($keys) {
            $this->resque->redis()->del($keys);
        }
    }

    protected function lonerKey($queue, $item, Loner $loner)
    {
        $class = $item['class'];
        $args = $item['args'];

        if ($loner->keyMethod) {
            $method = $loner->keyMethod;
            $key = $class::$method($class, $args, $loner);
        } else {
            if (is_array($args)) {
                ksort($args);
            }
            $key = md5(json_encode(array($class, $args)));
        }

        return "loners:queue:$queue:job:$key";
    }
}
