<?php

namespace ShonM\ResqueBundle\Listener;

use Doctrine\Common\Annotations\Reader,
    ShonM\ResqueBundle\Resque,
    ShonM\ResqueBundle\Annotation\Throttled,
    ShonM\ResqueBundle\Annotation\Loner,
    ShonM\ResqueBundle\Exception\ThrottledException,
    ReflectionClass,
    Resque\Job;

class ResqueListener
{
    protected $annotationReader;
    protected $resque;

    public function __construct(Resque $resque, Reader $annotationReader)
    {
        $this->resque = $resque;
        $this->annotationReader = $annotationReader;
    }

    public function onBeforeEnqueue($eventArg)
    {
        $class = $eventArg->getClass();
        $throttle = $this->getThrottleAnnotation($class);
        $throttleKey = $this->throttleKey($class, $throttle);
        if ($throttle && $this->shouldThrottle($throttleKey, $throttle)) {
            throw new ThrottledException("'$class' with key '$throttleKey' has exceeded it's throttle limit.");
        }
    }

    protected function getThrottleAnnotation($class)
    {
        return $this->annotationReader->getClassAnnotation(
            new ReflectionClass($class),
            'ShonM\ResqueBundle\Annotation\Throttled'
        );
    }

    protected function shouldThrottle($key, Throttled $throttle)
    {
        $redis = $this->resque->redis();
        $result = (bool) $redis->exists($key);
        if ($result === false) {
            $redis->set($key, true, $throttle->canRunEvery);
        }

        return $result;
    }

    protected function throttleKey($class, $throttle)
    {
        $key = $class;
        if ($throttle->keyMethod) {
            $method = $throttle->keyMethod;
            $key = $class::$method($throttle);
        }

        return "throttle:$key";
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

    protected function isLonerQueued($queue, $item)
    {
        $loner = $this->annotationReader->getClassAnnotation($item['class'], 'ShonM\ResqueBundle\Annotation\Loner');
        if (! $loner) {
            return false;
        }

        return $this->resque->redis()->get($this->lonerKey($queue, $item, $loner)) == "1";
    }

    protected function markLonerAsQueued($queue, $item)
    {
        $loner = $this->annotationReader->getClassAnnotation($item['class'], 'ShonM\ResqueBundle\Annotation\Loner');
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

    protected function markLonerAsUnqueued($queue, $job)
    {
        $item = $job instanceof Job ? $job->payload : $job;
        $loner = $this->annotationReader->getClassAnnotation($item['class'], 'ShonM\ResqueBundle\Annotation\Loner');
        if ($loner) {
            $this->resque->redis()->del($this->lonerKey($queue, $item, $loner));
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
}
