<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class RestartWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        // TODO: SM: Take a bunch of different options like worker ID's, maybe queue names, etc
        $this->setName('resque:worker:restart')
             ->setDescription('Restart workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('resque');

        $workers = $resque->workers();

        // TODO: SM: We should be a little smarter about
        //     1) How we are killing workers
        //     2) How we start new workers in their places
        foreach ($workers as $worker) {
            list($machine, $process, $queue) = explode(':', $worker->getId());
            exec('kill -SIGQUIT ' . $process);

            $output->writeln($worker->getId() . ' restarting');

            $worker = $this->getContainer()->get('resque.worker_daemon');
            $worker->defineQueue($queue);
            $worker->daemonize();
        }
    }
}
