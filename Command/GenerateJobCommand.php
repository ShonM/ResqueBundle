<?php

namespace ShonM\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ShonM\ResqueBundle\Generator\JobGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Sensio\Bundle\GeneratorBundle\Command\Validators as SensioValidators;

class GenerateJobCommand extends ContainerAwareCommand
{
    private $generator;

    /**
     * @see Command
     */
    public function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption(
                    'job',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The name of the job to create'
                ),
                new InputOption(
                    'job-type',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The type of job to create (containeraware, synchronous, throttled, loner)',
                    'containeraware'
                ),
            ))
            ->setDescription('Generates a job')
            ->setHelp(<<<EOT
The <info>resque:generate:job</info> command helps you generates new jobs
inside bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--bundle</comment> and <comment>--job</comment> are the only
ones needed if you follow the conventions):

<info>php app/console resque:generate:job --job=AcmeJobsBundle:SendEmail</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment>
but don't forget to pass all needed options:

<info>php app/console resque:generate:job --job=AcmeJobsBundle:SendEmail --no-interaction</info>
EOT
            )
            ->setName('resque:generate:job')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        if (null === $input->getOption('job')) {
            throw new \RuntimeException('The job option must be provided.');
        }

        list($bundle, $job) = $this->parseShortcutNotation($input->getOption('job'));
        if (is_string($bundle)) {
            $bundle = SensioValidators::validateBundleName($bundle);

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exists.</>', $bundle));
            }
        }

        $dialog->writeSection($output, 'Job generation');

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $job, $input->getOption('job-type'));

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $dialog->writeGeneratorSummary($output, array());
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the ShonMResqueBundle job generator');

        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate jobs easily.',
            '',
            'First, you need to give the job name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeJobsBundle:SendEmail</comment>',
            '',
        ));

        while (true) {
            $job = $dialog->askAndValidate($output, $dialog->getQuestion('Job name', $input->getOption('job')), array('ShonM\ResqueBundle\Command\Validators', 'validateJob'), false, $input->getOption('job'));
            list($bundle, $job) = $this->parseShortcutNotation($job);

            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);

                if (!file_exists($b->getPath().'/Job/'.$job.'Job.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Job "%s:%s" already exists.</>', $bundle, $job));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exists.</>', $bundle));
            }
        }
        $input->setOption('job', $bundle . ':' . $job);

        // job type
        $output->writeln(array(
            '',
            'Determine the type of job you want to create.',
            '',
        ));

        $jobType = $dialog->askAndValidate($output, $dialog->getQuestion('Job type (containeraware, synchronous, throttled or loner)', $input->getOption('job-type')), array('ShonM\ResqueBundle\Command\Validators', 'validateJobType'), false, $input->getOption('job-type'));
        $input->setOption('job-type', $jobType);

        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg-white', true),
            '',
            sprintf('You are going to generate a "<info>%s:%s</info>" job', $bundle, $job),
            sprintf('using the "<info>%s</info>" job type', $jobType),
        ));
    }

    public function getPlaceholdersFromRoute($route)
    {
        preg_match_all('/{(.*?)}/', $route, $placeholders);
        $placeholders = $placeholders[1];

        return $placeholders;
    }

    public function parseShortcutNotation($shortcut)
    {
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new \InvalidArgumentException(sprintf('The job name must contain a : ("%s" given, expecting something like AcmeJobsBundle:SendEmail)', $entity));
        }

        return array(substr($entity, 0, $pos), substr($entity, $pos + 1));
    }

    protected function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = new JobGenerator($this->getContainer()->get('filesystem'), __DIR__.'/../Resources/skeleton/job');
        }

        return $this->generator;
    }

    protected  function setGenerator(ControllerGenerator $generator)
    {
        $this->generator = $generator;
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog || get_class($dialog) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set($dialog = new DialogHelper());
        }

        return $dialog;
    }

}