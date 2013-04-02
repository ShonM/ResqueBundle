<?php

namespace ShonM\ResqueBundle;

class SchedulerDaemon
{
    protected $scheduler;
    protected $pollSleepAmount = 5;
    protected $verbose = false;
    protected $mute = false;
    private $sleeping = false;
    private $shutdown = false;

    public function __construct(ResqueScheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    public function getPollSleepAmount()
    {
        return $this->pollSleepAmount;
    }

    public function setPollSleepAmount($pollSleepAmount)
    {
        if ( !is_numeric($pollSleepAmount)) {
            throw new \RuntimeException('Poll sleep amount must be an integer.');
        }
        $this->pollSleepAmount = $pollSleepAmount;
    }

    public function getMute()
    {
        return $this->mute;
    }

    public function setMute($mute)
    {
        $this->mute = (bool) $mute;
    }

    public function getVerbose()
    {
        return $this->verbose;
    }

    public function setVerbose($verbose)
    {
        $this->verbose = (bool) $verbose;
    }

    public function run()
    {
        $this->procline('Starting');
        $this->registerSignalHandlers();

        while (true) {
            try {
                $this->handleDelayedItems();
            } catch (\Exception $e) {
                $this->log((string) $e);
            }

            $this->pollSleep();
        }
    }

    public function handleDelayedItems($atTime = null)
    {
        if ($timestamp = $this->scheduler->nextDelayedTimestamp($atTime)) {
            $this->procline("Processing Delayed Items");
            while ($timestamp) {
                $this->enqueueDelayedItemsForTimestamp($timestamp);
                $timestamp = $this->scheduler->nextDelayedTimestamp($atTime);
            }
            $this->procline("Sleeping");
        }
    }

    public function enqueueDelayedItemsForTimestamp($timestamp)
    {
        if ($this->shutdown) {
            exit;
        }

        while ($item = $this->scheduler->nextItemForTimestamp($timestamp)) {
            $this->log("queueing {$item['class']} [delayed]\n");
            try {
                $this->enqueueFromConfig($item);
            } catch (\Exception $e) {
                $this->log((string) $e);
            }
        }

        if ($this->shutdown) {
            exit;
        }
    }

    public function enqueueFromConfig($jobConfig)
    {
        $queue = isset($jobConfig['queue']) ? $jobConfig['queue'] : $this->scheduler->queueFromClass($jobConfig['class']);

        $this->scheduler->getResque()->add($jobConfig['class'], $queue, $jobConfig['args']);
    }

    public function pollSleep()
    {
        if ($this->shutdown) {
            exit;
        }

        $this->sleeping = true;
        sleep($this->pollSleepAmount);
        $this->sleeping = false;

        if ($this->shutdown) {
            exit;
        }
    }

    public function shutdown()
    {
        if ($this->sleeping) {
            exit;
        }
        $this->shutdown = true;
    }

    protected function procline($message)
    {
        $this->log($message);
        if (function_exists('setproctitle')) {
            setproctitle("resque-scheduler: $message");
        }
    }

    protected function registerSignalHandlers()
    {
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'shutdown'));
        pcntl_signal(SIGINT, array($this, 'shutdown'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
    }

    private function log($message)
    {
        if (! $this->mute && $this->verbose) {
            fwrite(STDOUT, date('Y-m-d H:i:s') . ' ' . $message . "\n");
        }
    }
}
