<?php

namespace ShonM\ResqueBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Loner extends Annotation
{
    public $keyMethod = false;

    public $ttl = 30;
}
