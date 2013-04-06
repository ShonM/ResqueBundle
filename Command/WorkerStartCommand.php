<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Resque\Job\Strategy\Fork,
    Resque\Job\Strategy\BatchFork,
    Resque\Job\Strategy\Fastcgi,
    Resque\Job\Strategy\InProcess;

class WorkerStartCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:worker:start')
             ->setDescription('Starts Resque worker(s)')
             ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*')
             ->addOption('no-daemonize', null, InputOption::VALUE_NONE, 'Execute worker inline')
             ->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Log mode [verbose|normal|none]')
             ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Daemon check interval (in seconds)', 5)
             ->addOption('forkCount', 'f', InputOption::VALUE_OPTIONAL, 'Fork instances count', 1)
             ->addOption('strategy', null, InputOption::VALUE_OPTIONAL, 'Job strategy [fork|batchfork|fastcgi|inprocess]', 'fork')
             ->addOption('perChild', null, InputOption::VALUE_OPTIONAL, 'If strategy "batchfork" is used, this is the number of jobs between forks, 0 is unlimited', 0)
        ;
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

        switch ($input->getOption('strategy')) {
            case 'fork':
                if (! extension_loaded('pcntl')) {
                    throw new \RuntimeException('To use the fork strategy, pcntl must be loaded', 1);
                }

                $jobStrategy = new Fork;
                break;
            case 'batchfork':
                if (! extension_loaded('pcntl')) {
                    throw new \RuntimeException('To use the batchfork strategy, pcntl must be loaded', 1);
                }

                $jobStrategy = new BatchFork((int) $input->getOption('perChild'));
                break;
            case 'fastcgi':
                $options = $container->hasParameter('resque.strategies.fastcgi') ? $container->getParameter('resque.strategies.fastcgi') : array();
                $fastcgiWorker = (! empty($options['worker'])) ? $options['worker'] : __DIR__ . '/../Resources/extras/fastcgi_worker.php';

                $jobStrategy = new Fastcgi(
                    '127.0.0.1:9000',
                    realpath($fastcgiWorker),
                    array(
                        'BASE_DIR'    => $container->get('kernel')->getRootDir(),
                        'ENVIRONMENT' => $container->get('kernel')->getEnvironment(),
                    )
                );
                break;
            case 'inprocess':
                $jobStrategy = new InProcess;
                break;
            default:
                throw new \InvalidArgumentException('The job strategy ' . $input->getOption('strategy') . ' does not exist');
                break;
        }

        $worker->setJobStrategy($jobStrategy);

        if ($input->getOption('no-daemonize')) {
            $worker->work();
        } else {
            fwrite(STDOUT, "Daemonizing\n");
            $worker->daemonize();
        }
    }
}
