<?php

namespace ShonM\ResqueBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ShonM\ResqueBundle\Resque;
use ShonM\ResqueBundle\Annotation\Throttled;
use ShonM\ResqueBundle\Exception\ThrottledException;
use ReflectionClass;

class ThrottleListener
{
    protected $resque;

    protected $annotationReader;

    public function __construct(Resque $resque, Reader $annotationReader)
    {
        $this->resque = $resque;
        $this->annotationReader = $annotationReader;

        AnnotationRegistry::registerFile(__DIR__ . '/../Annotation/Throttled.php');
    }

    public function onBeforeEnqueue($event)
    {
        $class = $event->getClass();
        $throttle = $this->getThrottleAnnotation($class);
        if ($throttle) {
            $throttleKey = $this->throttleKey($class, $throttle);
            $ttl = $this->shouldThrottle($throttleKey, $throttle);

            if ($ttl > 0) {
                throw new ThrottledException("'$class' with key '$throttleKey' has exceeded it's throttle limit for another $ttl seconds.");
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
        $result = (int) $redis->ttl($key);

        if ($result <= 0) {
            $redis->set($key, true, $throttle->canRunEvery);
        }

        return $result;
    }

    protected function throttleKey($class, $throttle)
    {
        $key = $class;
        if (! empty($throttle->keyMethod)) {
            $method = $throttle->keyMethod;
            $key = $class::$method($throttle);
        }

        return "throttle:$key";
    }
}
