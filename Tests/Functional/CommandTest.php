<?php

namespace ShonM\ResqueBundle\Tests\Functional;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Resque\Event;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    private $app;
    private $kernel;
    private static $container;

    protected function setUp()
    {
        $this->kernel = new AppKernel();

        $this->app = new Application($this->kernel);
        $this->app->setAutoExit(false);
        $this->app->setCatchExceptions(false);

        $this->resque = $this->getContainer()->get('resque');
    }

    protected function tearDown()
    {
        $this->resque = null;
        Event::clearListeners();
    }

    private function getContainer()
    {
        if (! self::$container) {
            $this->kernel->boot();
            self::$container = $this->kernel->getContainer();
        }

        return self::$container;
    }

    private function runCommand($command)
    {
        $output = new MemoryOutput();
        $input = new StringInput($command);

        $this->app->run($input, $output);

        return $output->getOutput();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUntrackedJobStatus()
    {
        $this->runCommand('resque:job:status 123');
    }

    public function testSuccessfulEnqueue()
    {
        $jobId = $this->resque->add('ShonM\ResqueBundle\Job\TestJob', 'test');

        $this->assertTrue(is_string($jobId));

        return $jobId;
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testWaitingStatus($jobId)
    {
        $status = $this->runCommand('resque:job:status ' . $jobId);

        $this->assertEquals(1, $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testUpdateStatus($jobId)
    {
        $status = $this->runCommand('resque:job:update ' . $jobId . ' ' . 2);

        $this->assertEquals('Job updated!', $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testUpdatedStatus($jobId)
    {
        $status = $this->runCommand('resque:job:status ' . $jobId);

        $this->assertEquals(2, $status);
    }

    /**
     * @depends testSuccessfulEnqueue
     */
    public function testQueueSize()
    {
        $size = $this->resque->size('test');

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

        $this->assertEquals(4, $status);
    }
}

class MemoryOutput extends Output
{
    private $output;

    protected function doWrite($message, $newline)
    {
        $this->output .= $message;

        if ($newline) {
            $this->output .= "\n";
        }
    }

    public function getOutput()
    {
        return $this->output;
    }
}
