<?php

namespace ShonM\ResqueBundle\Listener;

use Doctrine\Common\Annotations\Reader;
use ShonM\ResqueBundle\Resque;
use ShonM\ResqueBundle\Annotation\Loner;
use Resque\Job;

class Loner
{
    protected $resque;

    protected $annotationReader;

    public function __construct(Resque $resque, Reader $annotationReader)
    {
        $this->resque = $resque;
        $this->annotationReader = $annotationReader;
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

    // Not referenced
    protected function isLonerQueued($queue, $item)
    {
        $loner = $this->annotationReader->getClassAnnotation($item['class'], 'ShonM\ResqueBundle\Annotation\Loner');
        if (! $loner) {
            return false;
        }

        return $this->resque->redis()->get($this->lonerKey($queue, $item, $loner)) == "1";
    }

    // Not referenced
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

    // Not referenced
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

    // Not referenced
    protected function cleanupLoners($queue)
    {
        $keys = $this->resque->redis()->keys("loners:queue:$queue:job:*");
        if ($keys) {
            $this->resque->redis()->del($keys);
        }
    }
}
