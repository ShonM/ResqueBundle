<?php

namespace ShonM\ResqueBundle\Job;

/**
 * TODO: some way to stop a repeating task
 * TODO: don't repeat the task until the previous task completed?
 *       would have to be a JobInterface decorator instead of
 *       an independent Job then
 */
class Reschedule extends ContainerAwareJob
{
    public function perform ()
    {
        $scheduler = $this->container->get('resque.scheduler');
        call_user_func_array(array($scheduler, $this->args[0]), $this->args[1]);
    }
}
