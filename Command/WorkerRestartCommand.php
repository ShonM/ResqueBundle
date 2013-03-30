<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Process\Process,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    ShonM\ResqueBundle\Worker,
    RuntimeException,
    Resque_Worker;

class WorkerRestartCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:worker:restart')
             ->setDescription('Restart workers')
             ->addArgument('worker', InputArgument::OPTIONAL, 'Worker name')
             ->addOption('all', null, InputOption::VALUE_NONE, 'Restart all workers')
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

        // If there was a specific worker passed, check for it
        if ($target = $input->getArgument('worker')) {
            foreach ($workers as $worker) {
                if ($target == (string) $worker) {
                    return $this->restart($worker);
                }
            }

            return $output->writeln('<error>The worker specified could not be found</error>');
        }

        // If the all option was passed, just straight up restart everything
        if ($input->getOption('all')) {
            foreach ($workers as $worker) {
                $this->restart($worker);
            }
        } else {
            // Check if dialog helper supports choices, which makes things much easier for the user
            $dialog = $this->getHelper('dialog');
            if (method_exists($dialog, 'select')) {
                $pick = $dialog->select($output, 'Select a worker to restart:', $workers);
                $this->restart($workers[$pick]);
            } else {
                // Fall back to copy paste :(
                $output->writeln('Active workers (copy and paste to restart):');
                foreach ($workers as $worker) {
                    $output->writeln('    ' . $worker);
                }
            }
        }
    }

    private function restart(Worker $worker)
    {
        list($machine, $process, $queue) = explode(':', $worker->getId());

        $process = new Process('kill -QUIT ' . $process);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $this->output->writeln($worker->getId() . ' restarting');

        $worker = $this->getContainer()->get('resque.worker_daemon');
        $worker->defineQueue($queue);
        $worker->daemonize();
    }
}
