<?php

namespace ShonM\ResqueBundle\Tests\Functional;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\StringInput;

use Resque\Event;
use Resque\Job\Status;

class LonerCommandTest extends BaseCommandTest
{

    public function testSuccessfulEnqueue()
    {
        $jobId = $this->getResque()->add('ShonM\ResqueBundle\Job\LonelyTestJob', 'test');

        $this->assertTrue(is_string($jobId));

        return $jobId;
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
     * @expectedException ShonM\ResqueBundle\Exception\LonerException
     */
    public function testLonerEnqueue()
    {
        $this->getResque()->add('ShonM\ResqueBundle\Job\LonelyTestJob', 'test');
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testQueueSizeDoesntGrow()
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
