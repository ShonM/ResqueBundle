<?php

namespace ShonM\ResqueBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class JobGenerator extends Generator
{
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem A Filesystem instance
     * @param $skeletonDir the directory location of the job skeleton templates
     */
    public function __construct(Filesystem $filesystem, $skeletonDir)
    {
        $this->filesystem = $filesystem;
        $this->skeletonDir = $skeletonDir;
    }

    public function generate(BundleInterface $bundle, $job, $jobType)
    {
        $directory = $bundle->getPath();
        $jobFile = $directory . '/Job/' . $job . 'Job.php';
        if (file_exists($jobFile)) {
            throw new \RuntimeException(sprintf('Job "%s" already exists', $job));
        }

        $parameters = array(
            'namespace' => $bundle->getNamespace(),
            'bundle'    => $bundle->getName(),
            'job'       => $job,
            'type'      => $jobType,
        );

        // create a job
        switch($jobType) {
            case 'containeraware':
                $jobTemplate = 'ContainerAwareJob.php';
                break;
            case 'synchronous':
                $jobTemplate = 'SynchronousJob.php';
                break;
            case 'throttled':
                $jobTemplate = 'ThrottledJob.php';
                break;
            case 'loner':
                $jobTemplate = 'LonerJob.php';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('The job type format must be containeraware, synchronous, throttled or loner. "%s" given', $jobType));
                break;

        }

        $this->renderFile($this->skeletonDir, $jobTemplate, $jobFile, $parameters);
    }
}