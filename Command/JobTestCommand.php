<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class JobTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:job:test')
             ->setDescription('Enqueue\'s a job for testing')
             ->addOption('fail', null, InputOption::VALUE_NONE, 'If passed, will throw an exception')
             ->addOption('times', null, InputOption::VALUE_OPTIONAL, 'Times the job should be enqueued', 1)
             ->addOption('in', null, InputOption::VALUE_OPTIONAL, 'Seconds before enqueueing (requires an active scheduler)', 0)
             ->addOption('at', null, InputOption::VALUE_OPTIONAL, 'Timestamp at which enqueue should happen (requires an active scheduler)', 0)
             ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', '*')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i = $input->getOption('times');
        while ($i > 0) {
            $this->enqueue($input);
            $i--;
        }

        return;
    }

    protected function enqueue(InputInterface $input)
    {
        $resque = $this->getContainer()->get('resque');

        $class = 'ShonM\\ResqueBundle\\Jobs\\TestJob';
        $args = array(
            'fail' => $input->getOption('fail'),
        );

        if ($input->getOption('in')) {
            $scheduler = $this->getContainer()->get('resque.scheduler');
            return $scheduler->enqueueAtWithQueue($input->getArgument('queue'), $input->getOption('in'), $class, $args);
        }

        if ($input->getOption('at')) {
            $scheduler = $this->getContainer()->get('resque.scheduler');
            return $scheduler->enqueueInWithQueue($input->getArgument('queue'), $input->getOption('at'), $class, $args);
        }

        return $this->getContainer()->get('resque')->add($class, $input->getArgument('queue'), $args);
    }
}
