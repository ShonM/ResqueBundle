<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:worker')
            ->setDescription("Starts Resque worker(s)")
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*')
            ->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Log mode [verbose|normal|none]')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Daemon check interval (in seconds)', 5)
            ->addOption('forkCount', 'f', InputOption::VALUE_OPTIONAL, 'Fork instances count', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // We have to fetch our resque object first to make sure events get hooked
        $this->getContainer()->get('resque');

        $worker = $this->getContainer()->get('resque.worker_daemon');
        $worker->defineQueue($input->getArgument('queue'));
        $worker->verbose($input->getOption('log'));
        $worker->setInterval($input->getOption('interval'));
        $worker->forkInstances($input->getOption('forkCount'));

        fwrite(STDOUT, "Daemonizing\n");
        $worker->daemonize();
    }
}
