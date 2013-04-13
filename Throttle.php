<?php

namespace ShonM\ResqueBundle\Listener;

use Doctrine\Common\Annotations\Reader;
use ShonM\ResqueBundle\Resque;
use ShonM\ResqueBundle\Annotation\Throttled;
use ShonM\ResqueBundle\Exception\ThrottledException;
use ReflectionClass;

class Throttle
{
    protected $resque;

    protected $annotationReader;

    public function __construct(Resque $resque, Reader $annotationReader)
    {
        $this->resque = $resque;
        $this->annotationReader = $annotationReader;
    }

    // Not referenced
    public function onBeforeEnqueue($eventArg)
    {
        $class = $eventArg->getClass();
        $throttle = $this->getThrottleAnnotation($class);
        if ($throttle) {
            $throttleKey = $this->throttleKey($class, $throttle);
            if ($this->shouldThrottle($throttleKey, $throttle)) {
                throw new ThrottledException("'$class' with key '$throttleKey' has exceeded it's throttle limit.");
            }
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
}
