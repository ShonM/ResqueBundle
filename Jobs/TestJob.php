<?php

namespace ShonM\ResqueBundle\Jobs;

class TestJob
{
    public function perform ()
    {
        if (! empty($this->args['fail'])) {
            $this->job->worker->log("\x1B[31m" . 'Throwing a job - Goodbye from TestJob!' . "\x1B[39m" . "\n");
            throw new \Exception("This is a completely empty exception");
        }

        $this->job->worker->log("\x1B[31m" . 'Performing a job - Hello from TestJob!' . "\x1B[39m" . "\n");
    }
}
