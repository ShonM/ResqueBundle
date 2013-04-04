<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Resque_Worker;

class WorkerPruneCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:worker:prune')
             ->setDescription('Prunes inactive workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $resque = $this->getContainer()->get('resque');

        // Prune dead workers first
        $worker = new Resque_Worker('*');
        $worker->pruneDeadWorkers();

        // Now find all existing workers
        $workers = $resque->workers();

        if (! $workers) {
            return $output->writeln('<error>There are no available workers</error>');
        }
    }
}
