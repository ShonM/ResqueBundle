<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class EmptyJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:test')
            ->setDescription("Enqueue's an empty job for testing")
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->getContainer()->get('resque')->add('ShonM\ResqueBundle\Jobs\EmptyJob', $input->getArgument('queue'), array());
    }
}
