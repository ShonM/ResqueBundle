<?php

namespace ShonM\ResqueBundle;

use Cron\CronExpression;

/**
 * An additional daemon, the Scheduler, is responsible for taking items out of the timestamp queue
 * and into the real queue.
 */
class ResqueScheduler
{
    private $resque;

    public function __construct(Resque $resque)
    {
        $this->resque = $resque;
    }

    public function getResque()
    {
        return $this->resque;
    }

    public function enqueue($klass, $args)
    {
        $this->resque->add($klass, $this->queueFromClass($klass), $args);
    }

    public function enqueueWithQueue($queue, $klass, $args)
    {
        $this->resque->add($klass, $queue, $args);
    }

    public function enqueueAt($timestamp, $klass, array $args)
    {
        $this->validateJob($klass);
        $this->enqueueAtWithQueue($this->queueFromClass($klass), $timestamp, $klass, $args);
    }

    public function enqueueAtWithQueue($queue, $timestamp, $klass, $args)
    {
        $this->delayedPush($timestamp, $this->jobToHashWithQueue($queue, $klass, $args));
    }

    public function enqueueIn($secondsFromNow, $klass, array $args)
    {
        $this->enqueueAt(time() + $secondsFromNow, $klass, $args);
    }

    public function enqueueInWithQueue($queue, $secondsFromNow, $klass, array $args)
    {
        $this->enqueueAtWithQueue($queue, time() + $secondsFromNow, $klass, $args);
    }

    public function enqueueEvery($seconds, $klass, array $args)
    {
        $this->enqueueIn($seconds, $klass, $args);
        $this->enqueueIn($seconds, 'ShonM\ResqueBundle\Jobs\Reschedule', array(__FUNCTION__, func_get_args()));
    }

    public function enqueueEveryWithQueue($queue, $seconds, $klass, array $args)
    {
        $this->enqueueInWithQueue($queue, $seconds, $klass, $args);
        $this->enqueueIn($seconds, 'ShonM\ResqueBundle\Jobs\Reschedule', array(__FUNCTION__, func_get_args()));
    }

    public function enqueueCron($expression, $klass, array $args)
    {
        $timestamp = CronExpression::factory($expression)->getNextRunDate()->getTimestamp();
        $this->enqueueAt($timestamp, $klass, $args);
        $this->enqueueAt($timestamp, 'ShonM\ResqueBundle\Jobs\Reschedule', array(__FUNCTION__, func_get_args()));
    }

    public function enqueueCronWithQueue($queue, $expression, $klass, array $args)
    {
        $timestamp = CronExpression::factory($expression)->getNextRunDate()->getTimestamp();
        $this->enqueueAtWithQueue($queue, $timestamp, $klass, $args);
        $this->enqueueAt($timestamp, 'ShonM\ResqueBundle\Jobs\Reschedule', array(__FUNCTION__, func_get_args()));
    }

    public function delayedPush($timestamp, $item)
    {
        $redis = $this->resque->redis();
        $redis->rpush('delayed:' . $timestamp, $this->encode($item));
        $redis->zadd('delayed_queue_schedule', $timestamp, $timestamp);
    }

    public function delayedQueuePeek($start, $count)
    {
        return $this->resque->redis()->zrange('delayed_queue_schedule', $start, $start+$count);
    }

    public function delayedQueueScheduleSize()
    {
        return $this->resque->redis()->zcard('delayed_queue_schedule');
    }

    public function delayedTimestampSize($timestamp)
    {
        return $this->resque->redis()->llen("delayed:$timestamp");
    }

    public function delayedTimestampPeek($timestamp, $start, $count)
    {
        $result = $this->zredis->lrange("delayed:$timestamp", $start, $count);
        if (1 === $count) {
            $result = $result ? array($result) : array();
        }

        return array_map(array($this, 'decode'), $result);
    }

    /**
     * Internal - Returns the next delayed queue timestamp
     * @param  int   $atTime Timestamp to check
     * @return mixed ID of the job if found, else null
     */
    public function nextDelayedTimestamp($atTime = null)
    {
        $items = $this->resque->redis()->zrangebyscore('delayed_queue_schedule', '-inf', $atTime ?: time(), 'LIMIT', 0, 1);

        return $items ? $items[0] : null;
    }

    /**
     * Internal - Returns the next item to be processed for a given timestamp, null if done
     * @param  int   $timestamp Timestamp to check
     * @return mixed ID of the job if found, else null
     */
    public function nextItemForTimestamp($timestamp)
    {
        $key = "delayed:$timestamp";
        $item = $this->decode($this->resque->redis()->lpop($key));
        $this->cleanUpTimestamp($key, $timestamp);

        return $item;
    }

    /**
     * Clears all jobs created with enqueueAt or enqueueIn
     * @return null
     */
    public function resetDelayedQueue()
    {
        $redis = $this->resque->redis();
        foreach ($redis->zrange('delayed_queue_schedule', 0, -1) as $timestamp) {
            $redis->del("delayed:$timestamp");
        }

        $redis->del('delayed_queue_schedule');
    }

    /**
     * Given an encoded item, remove it from the delayed_queue
     *   This method is potentially very expensive since it needs to scan through the delayed queue for every timestamp.
     * @param  string $klass Class of the job to remove
     * @param  array  $args  Arguments of the job to remove
     * @return int    1 if destroyed, 0 if not found
     */
    public function removeDelayed($klass, array $args)
    {
        $destroyed = 0;
        $encodedJobHash = $this->encode($this->jobToHash($klass, $args));
        foreach ($this->resque->redis()->zrange('delayed_queue_schedule', 0, -1) as $timestamp) {
            $destroyed += $this->removeDelayedJobFromTimestamp($timestamp, $encodedJobHash);
        }

        return $destroyed;
    }

    protected function removeDelayedJobFromTimestamp($timestamp, $encodedJobHash)
    {
        $key = "delayed:$timestamp";
        $count = $this->resque->redis()->lrem($key, 0, $encodedJobHash);
        $this->cleanUpTimestamp($key, $timestamp);

        return $count;
    }

    public function countAllScheduledJobs()
    {
        $totalJobs = 0;
        $redis = $this->resque->redis();
        foreach ($redis->zrange('delayed_queue_schedule', 0, -1) as $timestamp) {
            $totalJobs += $redis->llen("delayed:$timestamp");
        }

        return $totalJobs;
    }

    public function queueFromClass()
    {
        return 'default';
    }

    private function jobToHash($klass, array $args)
    {
        return $this->jobToHashWithQueue($this->queueFromClass($klass), $klass, $args);
    }

    private function jobToHashWithQueue($queue, $klass, array $args)
    {
        return array(
            'class' => $klass,
            'args' => $args,
            'queue' => $queue,
        );
    }

    private function validateJob($klass)
    {
        if (! $klass) {
            throw new \Exception('Jobs must be given a class.');
        }

        if ( ! $this->queueFromClass($klass)) {
            throw new \Exception('Jobs must be placed onto a queue.');
        }
    }

    private function cleanUpTimestamp($key, $timestamp)
    {
        $redis = $this->resque->redis();
        if (0 == $redis->llen($key)) {
            $redis->del($key);
            $redis->zrem('delayed_queue_schedule', $timestamp);
        }
    }

    private function encode($value)
    {
        return json_encode($value);
    }

    private function decode($value)
    {
        return json_decode($value, true);
    }
}
