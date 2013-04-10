<?php

namespace ShonM\ResqueBundle;

use Resque\Worker as BaseWorker;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class Worker extends BaseWorker
{
    protected $job = null;
    protected $logger = null;

    public function __construct($queues, $logger)
    {
        parent::__construct($queues);
        $this->logger = $logger;
    }

    public function job()
    {
        if ($this->job === null) {
            $this->job = $this->worker->job();
        }

        return $this->job;
    }

    public function getQueues()
    {
        return array_map(function ($queue) {
            return new Queue($queue);
        }, $this->queues());
    }

    /**
     * Output a given log message via Monolog.
     *
     * @param string $message  Message to output.
     * @param int    $logLevel The logging level to capture
     */
    public function log($message, $logLevel = self::LOG_DEBUG)
    {
        if ($logLevel > $this->logLevel) {
            return;
        }

        if ( ! $this->logger instanceOf LoggerInterface) {
            if ( ! defined('STDOUT')) return;
            fwrite(STDOUT, $message . PHP_EOL);
            return;
        }

        switch ($logLevel) {
            case self::LOG_DEBUG    : $this->logger->debug($message); break;
            case self::LOG_INFO     : $this->logger->info($message); break;
            case self::LOG_NOTICE   : $this->logger->notice($message); break;
            case self::LOG_WARNING  : $this->logger->warn($message); break;
            case self::LOG_ERROR    : $this->logger->err($message); break;
            case self::LOG_CRITICAL : $this->logger->crit($message); break;
            case self::LOG_ALERT    : $this->logger->emerg($message); break;
            default: break;
        }
    }

}
