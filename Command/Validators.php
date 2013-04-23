<?php

namespace ShonM\ResqueBundle\Command;

/**
 * Validator functions.
 */
class Validators
{
    public static function validateJob($job)
    {
        if (false === $pos = strpos($job, ':')) {
            throw new \InvalidArgumentException(sprintf('The job name must contain a : ("%s" given, expecting something like AcmeWorkerBundle:SendEmail)', $job));
        }

        return $job;
    }

    public static function validateJobType($jobType)
    {
        $format = strtolower($jobType);

        if (!in_array($format, array('containeraware', 'synchronous', 'throttled', 'loner'))) {
            throw new \RuntimeException(sprintf('The job type must be "containeraware", "synchronous", "throttled" or "loner". "%s" given', $jobType));
        }

        return $format;
    }
}
