<?php

namespace ShonM\ResqueBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Throttled extends Annotation
{
    public $keyMethod = false;

    public $canRunEvery = 0;
}
