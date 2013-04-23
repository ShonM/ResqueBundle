<?php

namespace ShonM\ResqueBundle\Tests\Functional;

use Resque\Job\Status;

class CommandTest extends BaseCommandTest
{
    /**
     * @expectedException \RuntimeException
     */
    public function testUntrackedJobStatus()
    {
        $this->runCommand('resque:job:status 123');
    }

    public function testSuccessfulEnqueue()
    {
        $jobId = $this->getResque()->add('ShonM\ResqueBundle\Job\TestJob', 'test');

        $this->assertTrue(is_string($jobId));

        return $jobId;
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testWaitingStatus($jobId)
    {
        $status = $this->runCommand('resque:job:status ' . $jobId);

        $this->assertEquals(Status::STATUS_WAITING, $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testUpdateStatus($jobId)
    {
        $status = $this->runCommand('resque:job:update ' . $jobId . ' ' . Status::STATUS_RUNNING);

        $this->assertEquals('Job updated!', $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testUpdatedStatus($jobId)
    {
        $status = $this->runCommand('resque:job:status ' . $jobId);

        $this->assertEquals(Status::STATUS_RUNNING, $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testQueueSize()
    {
        $size = $this->getResque()->size('test');

        $this->assertEquals(1, $size);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testProcessing($job)
    {
        $this->runCommand('resque:worker:start test --interval=0');

        usleep(100000);

        $status = $this->runCommand('resque:job:status ' . $job);

        $this->assertEquals(Status::STATUS_COMPLETE, $status);
    }
}
