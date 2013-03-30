<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class JobStatusCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:job:status')
             ->setDescription('Check Job status')
             ->addArgument('job_id', InputArgument::REQUIRED, 'Job ID')
             ->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Redis Namespace (prefix)')
             ->setHelp('Check a Job status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = $this->getContainer()
            ->get('resque')
            ->check($input->getArgument('job_id'), $input->getOption('namespace'));

        $output->write($status);
    }
}
