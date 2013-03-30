<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class SchedulerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:scheduler')
             ->setDescription("Starts Resque scheduler to trigger future work queues")
             ->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Log mode [verbose|normal|none]')
             ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Daemon check interval (in seconds)', 5)
             ->addOption('foreground', 'f', InputOption::VALUE_NONE, 'Run in foreground')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! $input->getOption('foreground')) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                fwrite(STDOUT, "Failed Forking\n");
                die();
            } elseif ($pid) {
                die();
            }
        }

        $scheduler = $this->getContainer()->get('resque.scheduler_daemon');

        $scheduler->setPollSleepAmount($input->getOption('interval'));
        $verbose = $input->getOption('log');

        if ($verbose === 'verbose') {
            $scheduler->setVerbose(true);
        } elseif ($verbose == 'none') {
            $scheduler->setMute(true);
        }

        $scheduler->run();
    }
}
