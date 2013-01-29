<?php

namespace ShonM\ResqueBundle\Jobs;

use ShonM\ResqueBundle\Jobs\ContainerAwareJob;

class EmptyJob extends ContainerAwareJob
{
    public function perform ()
    {
        fwrite(STDOUT, "\x1B[31m" . 'Performing a job - Hello from EmptyJob!' . "\x1B[39m" . "\n");

        if ($this->args['fail']) {
            throw new \Exception("This is a completely empty exception");
        }
    }
}
