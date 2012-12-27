<?php

namespace ShonM\ResqueBundle\Tests\Functional\TestBundle\Command;

use Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputInterface;

class SuccessfulCommand extends \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:successful');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $this->getContainer()->get('resque')->add('ShonM\ResqueBundle\Jobs\EmptyJob');

        $output->write($jobId);
    }
}
