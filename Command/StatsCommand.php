<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Resque\Resque,
    Resque\Stat,
    Resque\Worker;

class StatsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('resque:stats')
             ->setDescription('Enqueue a job')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('resque');
        $backlog = 0;
        foreach ($resque->queues() as $queue) {
            $backlog += $queue->getSize();
        }

        $output->writeln('<info>Jobs</info>');
        $output->writeln('    Backlog : ' . $backlog);
        $output->writeln('    Processed : ' . Stat::get('processed'));
        $output->writeln('    Failed    : ' . Stat::get('failed'));
        $output->writeln('');

        $output->writeln('<info>Workers</info>');

        $workers = Worker::all();
        $output->writeln('    Active Workers : ' . count($workers));
        $output->writeln('');

        if (!empty($workers)) {
            foreach ($workers as $worker) {
                $output->writeln('    Worker : <comment>' . $worker . '</comment>');
                $output->writeln('        Started on     : ' . Resque::Redis()->get('worker:' . $worker . ':started'));
                $output->writeln('        Uptime         : ' . $this->uptime(Resque::Redis()->get('worker:' . $worker . ':started')));
                $output->writeln('        Processed Jobs : ' . $worker->getStat('processed'));
                $output->writeln('        Failed Jobs    : ' . $worker->getStat('failed'));
            }
        }
    }

    /**
     * Calculates a relative (human readable) time, like "5 minutes ago"
     * @param  int     $date
     * @param  integer $precision
     * @return string
     */
    public static function uptime($date, $precision = 2)
    {
        // initialize the string used to store the readable time and the current precision count
        $uptime = '';
        $curPrecision = 0;

        // define the intervals dictionary (in seconds)
        $intervals = array(
            'year' => 31556926,
            'month' => 2629744,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60);

        // grab the current time, convert the date to a UNIX timestamp, and calculate the difference between the two times
        $now = time();
        $date = strtotime($date);
        $diff = $now - $date;

        // if the difference is less than sixty, then the event just happened
        if ($diff < 60) {
            return '1 minute';
        }

        foreach ($intervals as $label => $seconds) {
            // if the current precision is equal to the precision needed, then break from the loop
            if ($curPrecision == $precision) {
                break;
            }

            // count the number of the total interval and then subtract the integer value from the difference
            $total = $diff / $seconds;
            $diff -= (intval($total) * $seconds);

            // check if the total is greater than or equal to one
            if ($total >= 1) {
                // round the total to the nearest whole number and initialize the plural value
                $total = round($total);
                $plural = '';

                // if the total is greater than one, then update the plural value
                if ($total > 1) {
                    $plural = 's';
                }

                // update the readable string and increment the current precision
                $uptime .= $total . ' ' . $label . $plural . ', ';
                $curPrecision++;
            }
        }

        // remove the trailing comma and spacen, then finish off the readable string
        $uptime = substr($uptime, 0, -2);

        // return the readable string
        return $uptime;
    }
}
