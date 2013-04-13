<?php

namespace ShonM\ResqueBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use ShonM\ResqueBundle\Event as Events;

class ResqueSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'resque.after_enqueue'     => array('onAfterEnqueue', 0),
            'resque.before_first_fork' => array('onBeforeFirstFork', 0),
            'resque.before_fork'       => array('onBeforeFork', 0),
            'resque.after_fork'        => array('onAfterFork', 0),
            'resque.before_perform'    => array('onBeforePerform', 0),
            'resque.after_perform'     => array('onAfterPerform', 0),
            'resque.on_failure'        => array('onFailure', 0),
        );
    }

    public function onAfterEnqueue(Events\AfterEnqueueEvent $event)
    {
    }

    public function onBeforeFirstFork(Events\BeforeFirstForkEvent $event)
    {
    }

    public function onBeforeFork(Events\BeforeForkEvent $event)
    {
    }

    public function onAfterFork(Events\AfterForkEvent $event)
    {
    }

    public function onBeforePerform(Events\BeforePerformEvent $event)
    {
        $instance = $event->getJob()->getInstance();

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($event->getContainer());
        }
    }

    public function onAfterPerform(Events\AfterPerformEvent $event)
    {
    }

    public function onFailure(Events\OnFailureEvent $event)
    {
    }
}
