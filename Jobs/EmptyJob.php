<?php

namespace ShonM\ResqueBundle\Jobs;

use ShonM\ResqueBundle\Jobs\ContainerAwareJob;

class EmptyJob extends ContainerAwareJob
{
    public function perform ()
    {
        if ($this->args['fail']) {
            fwrite(STDOUT, "\x1B[31m" . 'Throwing a job - Goodbye from EmptyJob!' . "\x1B[39m" . "\n");
            throw new \Exception("This is a completely empty exception");
        }

        fwrite(STDOUT, "\x1B[31m" . 'Performing a job - Hello from EmptyJob!' . "\x1B[39m" . "\n");
    }
}
