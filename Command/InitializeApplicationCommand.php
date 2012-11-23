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
 * InitializeBundleCommand extracts records to be translated from the current application
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class InitializeApplicationCommand extends AbstractCommand
{
    /**
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
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getContainer()->getParameter('kernel.root_dir');
        chdir("$root/..");
        $path = "$root/Resources/gettext/messages.pot";
        $languages = $input->getArgument('languages');
        $results = $this->initializeFromTemplate($path,$languages);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('languages')) {
            $languages = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please enter the list of languages (comma seperated):',
                function($languages)
                {
                  if (empty($languages)) {
                    throw new \Exception('Language list can not be empty');
                  }
          
                  return $languages;
                }
            );
            $input->setArgument('languages', $languages);
        }
    }
}
