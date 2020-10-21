<?php

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * InitializeBundleCommand extracts records to be translated from the specified bundle
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class InitializeBundleCommand extends AbstractCommand
{
    /**
     * Configures extractor
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:bundle:initialize')
            ->setDescription('Intialize translations from a bundle')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle'),
                new InputArgument('languages', InputArgument::REQUIRED, 'The language list'),
            ))
            ->setHelp(<<<EOT
The <info>gettext:bundle:initialize</info> command initialize translations for a bundle for specific languages:

  <info>php app/console gettext:bundle:initialize</info>

This interactive shell will first ask you for a bundle name.

You can alternatively specify the bundle as the first argument:

  <info>php app/console gettext:bundle:initialize FOSUserBundle</info>

This interactive shell will then ask you for a language list:

You can alternatively specify the comma-separated language list as the second argument:

  <info>php app/console gettext:bundle:initialize FOSUserBundle en_US,nl_NL,de_DE</info>

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
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getContainer()->getParameter('kernel.root_dir');
        chdir("$root/..");
        $bundle = $input->getArgument('bundle');
        $bundle = ltrim($bundle, '@');
        $bundleObj = $this->getContainer()->get('kernel')->getBundle($bundle);
        if (!$bundleObj) {
            throw new ResourceNotFoundException("Cannot load bundle resource '$bundle'");
        }
        $path = $bundleObj->getPath().'/Resources/gettext/messages.pot';
        $languages = $input->getArgument('languages');
        $results = $this->initializeFromTemplate($path, $languages);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }

        return 0;
    }

    /**
     * Method returns list of languages
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @see Command
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        if (!$input->getArgument('bundle')) {
            $bundleQuestion = new Question('Please give the bundle: ');
            $bundleQuestion->setValidator(function ($bundle) {
                if (empty($bundle)) {
                    throw new \RuntimeException(
                        'Bundle can not be empty'
                    );
                }

                return $bundle;
            });

            $bundle = $questionHelper->ask($input, $output, $bundleQuestion);
            $input->setArgument('bundle', $bundle);
        }

        if (!$input->getArgument('languages')) {
            $languageQuestion = new Question('Please enter the list of languages (comma seperated): ');
            $languageQuestion->setValidator(function ($languages) {
                if (empty($languages)) {
                    throw new \RuntimeException(
                        'Language list can not be empty'
                    );
                }

                return $languages;
            });

            $languages = $questionHelper->ask($input, $output, $languageQuestion);
            $input->setArgument('languages', $languages);
        }
    }
}