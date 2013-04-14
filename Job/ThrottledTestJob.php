<?php

namespace ShonM\ResqueBundle\Job;

use ShonM\ResqueBundle\Annotation\Throttled;

/**
 * @Throttled(canRunEvery=30)
 */
class ThrottledTestJob extends TestJob
{
}
