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

        if (!in_array($format, array('default', 'containeraware', 'synchronous', 'throttled', 'loner', 'none'))) {
            throw new \RuntimeException(sprintf('The job type must be "Default", "Containeraware", "Synchronous", "Throttled", "Loner" or "None. "%s" given', $jobType));
        }

        return $format;
    }
}
