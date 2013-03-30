<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class JobRetryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:job:retry')
             ->setDescription('Retries jobs by moving them from the failure list to their original queue')
             ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of jobs to retry', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('resque');

        while ($failure = $resque->redis()->lpop('failed')) {
            if (! $failure) {
                break;
            }

            $job = json_decode($failure, true);
            $resque->add($job['payload']['class'], $job['queue'], $job['payload']['args']);
            $resque->redis()->decr('failed');
        }
    }
}
