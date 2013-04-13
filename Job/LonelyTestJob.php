<?php

namespace ShonM\ResqueBundle\Job;

use ShonM\ResqueBundle\Annotation\Loner;

/**
 * @Loner(ttl=30)
 */
class LonelyTestJob extends TestJob
{
}
