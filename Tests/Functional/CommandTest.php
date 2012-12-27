<?php

namespace ShonM\ResqueBundle\Tests\Functional;

use Symfony\Component\Console\Output\Output,
    Symfony\Component\Console\Input\ArrayInput,
    Symfony\Bundle\FrameworkBundle\Console\Application;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    protected function setUp()
    {
        $kernel = new AppKernel();

        $this->app = new Application($kernel);
        $this->app->setAutoExit(false);
        $this->app->setCatchExceptions(false);
    }

    private function doRun(array $args = array())
    {
        $output = new MemoryOutput();
        $this->app->run(new ArrayInput($args), $output);

        return $output->getOutput();
    }

    public function testSuccessfulCommand()
    {
        $output = $this->doRun(array('resque:successful'));

        $this->assertNull($output);
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
