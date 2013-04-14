<?php

namespace ShonM\ResqueBundle\Job;

use Symfony\Component\Process\ProcessBuilder;
use Psr\Log\LoggerInterface;

class CommandJob extends ContainerAwareJob
{
    public function perform()
    {
        $pb = $this->getCommandProcessBuilder();
        $pb->add($this->args['command']);

        foreach ($this->args as $key => $arg) {
            if ($key !== "command") {
                $pb->add($arg);
            }
        }

        $process = $pb->getProcess();
        $process->run();

        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        if ($process->getExitCode() !== 0) {
            $logger->error($process->getExitCodeText(), array("error" => $process->getErrorOutput()));
        }
        $logger->info($process->getOutput());
    }

    private function getCommandProcessBuilder()
    {
        $pb = new ProcessBuilder();

        // PHP wraps the process in "sh -c" by default, but we need to control
        // the process directly.
        if ( ! defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $pb->add('exec');
        }

        $pb
            ->add('php')
            ->add($this->getContainer()->getParameter('kernel.root_dir').'/console')
            ->add('--env='.$this->getContainer()->get('kernel')->getEnvironment())
        ;

        return $pb;
    }
}
