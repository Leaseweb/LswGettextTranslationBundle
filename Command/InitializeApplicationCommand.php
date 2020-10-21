<?php

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * InitializeBundleCommand extracts records to be translated from the current application
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class InitializeApplicationCommand extends AbstractCommand
{
    /**
     * Configures extractor
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:app:initialize')
            ->setDescription('Intialize translations from the application')
            ->setDefinition(array(
                new InputArgument('languages', InputArgument::REQUIRED, 'The language list'),
            ))
            ->setHelp(<<<EOT
The <info>gettext:app:initialize</info> command initialize translations for the application for specific languages:

  <info>php app/console gettext:app:initialize</info>

This interactive shell will ask you for a language list.

You can alternatively specify the comma-separated language list as the first argument:

  <info>php app/console gettext:app:initialize en_US,nl_NL,de_DE</info>

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
        $path = "$root/Resources/gettext/messages.pot";
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
        if (!$input->getArgument('languages')) {
            $questionHelper = $this->getHelper('question');
            $question = new Question('Please enter the list of languages (comma seperated): ');
            $question->setValidator(function ($languages) {
                if (empty($languages)) {
                    throw new \RuntimeException(
                        'Language list can not be empty'
                    );
                }

                return $languages;
            });

            $languages = $questionHelper->ask($input, $output, $question);
            $input->setArgument('languages', $languages);
        }
    }
}