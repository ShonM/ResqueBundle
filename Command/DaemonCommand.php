<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Resque_JobStrategy_Fork,
    Resque_JobStrategy_Fastcgi,
    Resque_JobStrategy_InProcess;

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
            ->addOption('strategy', null, InputOption::VALUE_OPTIONAL, 'Job strategy [fork|fastcgi|inprocess]', 'fork');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        // We have to fetch our resque object first to make sure events get hooked
        $container->get('resque');

        $worker = $container->get('resque.worker_daemon');
        $worker->defineQueue($input->getArgument('queue'));
        $worker->verbose($input->getOption('log'));
        $worker->setInterval($input->getOption('interval'));
        $worker->forkInstances($input->getOption('forkCount'));

        switch($input->getOption('strategy')) {
            case 'fork':
                $jobStrategy = new Resque_JobStrategy_Fork;
                break;
            case 'fastcgi':
                $options = $container->hasParameter('resque.strategies.fastcgi') ? $container->getParameter('resque.strategies.fastcgi') : array();
                $fastcgiWorker = (! empty($options['worker'])) ? $options['worker'] : __DIR__ . '/../Resources/extras/fastcgi_worker.php';

                $jobStrategy = new Resque_JobStrategy_Fastcgi(
                    '127.0.0.1:9000',
                    realpath($fastcgiWorker),
                    array(
                        'BASE_DIR'    => $container->get('kernel')->getRootDir(),
                        'ENVIRONMENT' => $container->get('kernel')->getEnvironment(),
                    )
                );
                break;
            case 'inprocess':
                $jobStrategy = new Resque_JobStrategy_InProcess;
                break;
            default:
                throw new \InvalidArgumentException('The job strategy ' . $input->getOption('strategy') . ' does not exist');
                break;
        }

        $worker->setJobStrategy($jobStrategy);

        fwrite(STDOUT, "Daemonizing\n");
        $worker->daemonize();
    }
}
