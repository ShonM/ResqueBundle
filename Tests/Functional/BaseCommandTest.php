<?php

namespace ShonM\ResqueBundle\Tests\Functional;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Resque\Event;

abstract class BaseCommandTest extends \PHPUnit_Framework_TestCase
{
    private $app;
    private $kernel;
    private static $container;
    private $resque;

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

    protected function runCommand($command)
    {
        $output = new MemoryOutput();
        $input = new StringInput($command);

        $this->app->run($input, $output);

        return $output->getOutput();
    }

    protected function getResque()
    {
        return $this->resque;
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
