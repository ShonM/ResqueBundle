<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Process\Process,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    RuntimeException,
    Resque_Worker;

class WorkerRestartCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:worker:restart')
             ->setDescription('Restart workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('resque');

        // Prune dead workers first
        $worker = new Resque_Worker('*');
        $worker->pruneDeadWorkers();

        // Now find all existing workers
        $workers = $resque->workers();

        foreach ($workers as $worker) {
            list($machine, $process, $queue) = explode(':', $worker->getId());

            $process = new Process('kill -QUIT ' . $process);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput());
            }

            $output->writeln($worker->getId() . ' restarting');

            $worker = $this->getContainer()->get('resque.worker_daemon');
            $worker->defineQueue($queue);
            $worker->daemonize();
        }
    }
}
