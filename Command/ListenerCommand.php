<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ListenerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:listener');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $listener = $this->getContainer()->get('resque.listener');
    }
}
