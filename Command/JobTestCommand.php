<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class JobTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:job:test')
             ->setDescription('Enqueue\'s a job for testing')
             ->addOption('fail', null, InputOption::VALUE_NONE, 'If passed, will throw an exception')
             ->addOption('times', null, InputOption::VALUE_OPTIONAL, 'Times the job should be enqueued', 1)
             ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i = $input->getOption('times');
        while ($i > 0) {
            $this->queue($input);
            $i--;
        }

        return;
    }

    protected function queue(InputInterface $input)
    {
        return $this->getContainer()->get('resque')->add('ShonM\ResqueBundle\Jobs\TestJob', $input->getArgument('queue'), array(
            'fail' => $input->getOption('fail'),
        ));
    }
}
