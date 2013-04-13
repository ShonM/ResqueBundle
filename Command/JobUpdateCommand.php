<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class JobUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:job:update')
            ->setDescription('Update a Job status')
            ->addArgument('job_id', InputArgument::REQUIRED, 'The Job ID')
            ->addArgument('new_status', InputArgument::REQUIRED, 'New Status')
            ->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Redis Namespace (prefix)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $resque = $this->getContainer()->get('resque');
            if ($resque->update($input->getArgument('new_status'), $input->getArgument('job_id'), $input->getOption('namespace'))) {
                $output->write('Job updated!');
            } else {
                throw new \RuntimeException('Job could NOT be updated.');
            }
        } catch (\RuntimeException $e) {
            $output->write('ERROR attempting update: ' . $e->getMessage());
        }
    }
}
