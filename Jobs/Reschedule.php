<?php

namespace ShonM\ResqueBundle\Jobs;

/**
 * TODO: some way to stop a repeating task
 * TODO: dont repeat the task untill the previous task completed?
 *       would have to be a JobInterface decorator instead of
 *       an independant Job then
 */
class Reschedule extends ContainerAwareJob
{
    public function perform()
    {
        $scheduler = $this->container->get('resque.scheduler');
        call_user_func_array(array($scheduler, $this->args[0]), $this->args[1]);
    }
}
