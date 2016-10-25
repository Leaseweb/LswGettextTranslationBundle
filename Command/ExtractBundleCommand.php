<?php

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * ExtractBundleCommand extracts records to be translated from the specified bundle
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class ExtractBundleCommand extends AbstractCommand
{
    /**
     * Configures extractor
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:bundle:extract')
            ->setDescription('Extract translations from a bundle')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle'),
                new InputOption('keep-cache', null, InputOption::VALUE_NONE, 'Do not delete the intermediate twig.cache.php file'),
            ))
            ->setHelp(<<<EOT
The <info>gettext:bundle:extract</info> command extracts translations from a bundle:

  <info>php app/console gettext:bundle:extract</info>

This interactive shell will first ask you for a bundle name.

You can alternatively specify the bundle as the first argument:

  <info>php app/console gettext:bundle:extract FOSUserBundle</info>

You can keep the intermediate twig.cache.php file by specifying the keep-cache flag:

  <info>php app/console gettext:bundle:extract FOSUserBundle --keep-cache</info>

EOT
            );
    }

    /**
     * Execute method get an input texts prepare it for each locale
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $root = $container->getParameter('kernel.root_dir');
        $resourcesSubfolder = $container->getParameter('lsw_gettext_resources_subfolder');
        $messagesFile = $container->getParameter('lsw_gettext_messages_template_file');
        chdir("$root/..");
        $bundle = $input->getArgument('bundle');
        $bundle = ltrim($bundle, '@');
        $bundleObj = $this->getContainer()->get('kernel')->getBundle($bundle);
        if (!$bundleObj) {
            throw new ResourceNotFoundException("Cannot load bundle resource '$bundle'");
        }

        $path = $bundleObj->getPath() . $resourcesSubfolder . $messagesFile;
        $twig = $bundleObj->getPath() . $resourcesSubfolder . 'twig.cache.php';
        $results = $this->convertTwigToPhp($twig, $bundle);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
        $results = $this->extractFromPhp($path, $bundle);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
        if (!$input->getOption('keep-cache')) {
            unlink($twig);
        }
    }

    /**
     * Method returns list of languages
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @see Command
     * @return mixed
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('bundle')) {
            $bundle = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please give the bundle:',
                function($bundle)
                {
                    if (empty($bundle)) {
                        throw new \Exception('Bundle can not be empty');
                    }

                    return $bundle;
                }
            );
            $input->setArgument('bundle', $bundle);
        }
    }
}
