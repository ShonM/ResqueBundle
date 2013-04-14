<?php

namespace ShonM\ResqueBundle;

final class ResqueEvents
{
    /**
     * Fired before a job has been enqueued
     *
     * @var string
     */
    const BEFORE_ENQUEUE = 'resque.before_enqueue';

    /**
     * Fired after a job has been enqueued
     *
     * @var string
     */
    const AFTER_ENQUEUE = 'resque.after_enqueue';

    /**
     * Fired once after a worker is initialized, but before it registers for work
     *
     * @var string
     */
    const BEFORE_FIRST_FORK = 'resque.before_first_fork';

    /**
     * Fired before a worker forks a job, from the parent
     *
     * @var string
     */
    const BEFORE_FORK = 'resque.before_fork';

    /**
     * Fired after a job has been forked, from the child
     *
     * @var string
     */
    const AFTER_FORK = 'resque.after_fork';

    /**
     * Fired before the setUp and perform methods are called
     *
     * @var string
     */
    const BEFORE_PERFORM = 'resque.before_perform';

    /**
     * Fired after the perform and tearDown methods are called
     *
     * @var string
     */
    const AFTER_PERFORM = 'resque.after_perform';

    /**
     * Fired any time a job fails
     *
     * @var string
     */
    const ON_FAILURE = 'resque.on_failure';
}
