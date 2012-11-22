<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * ExtractApplicationCommand extracts records to be translated from the current application
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class CombineAllCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:combine')
            ->setDescription('Combines translations from all bundles and the application for specific languages')
            ->setDefinition(array(
                new InputArgument('languages', InputArgument::REQUIRED, 'The language list'),
                new InputOption('keep-messages', null, InputOption::VALUE_NONE, 'Do not delete the intermediate messages.po file'),
                new InputOption('increase-version', null, InputOption::VALUE_OPTIONAL, 'Increase the version of the mo file', true),
            ))
            ->setHelp(<<<EOT
The <info>gettext:combine</info> command combines translations from all 
bundles and the application for specific languages:

  <info>php app/console gettext:combine</info>

This interactive shell will ask you for a language list.
               
You can alternatively specify the comma-separated language list as the first argument:

  <info>php app/console gettext:combine en_US,nl_NL,de_DE</info>

You can keep the intermediate messages.po file by specifying the keep-messages flag:

  <info>php app/console gettext:combine en_US,nl_NL,de_DE --keep-messages</info>

EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getContainer()->getParameter('kernel.root_dir');
        chdir($root.'/..');

        $configFile = $root."/Resources/gettext/version";   
        $languages  = explode(',', trim($input->getArgument('languages'), ','));
        $bundles    = $this->getContainer()->get('kernel')->getBundles();
        $version    = file_exists($configFile) ? file_get_contents($configFile) : "";
        $newVersion = $input->getOption('increase-version') ? "_" . strtotime("now") : $version;

        foreach ($languages as $lang) {
            $lang = trim($lang);
            $files = array();
            // add the application translation file as the first file 
            // the msgcat --allow-first allows for override of bundle translations
            $file = "$root/Resources/gettext/locale/$lang/LC_MESSAGES/messages.po";
            if (file_exists($file)) $files[] = $file;
            // add the bundle translation files 
            foreach ($bundles as $bundleObj) {
                $file = $bundleObj->getPath()."/Resources/gettext/locale/$lang/LC_MESSAGES/messages.po";
                if (file_exists($file)) $files[] = $file;
            }

            $path = "$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$newVersion.po";
            $results = $this->combineFiles($files,$path);
            foreach ($results as $filename => $status) {
                $output->writeln("$status: $filename");
            }
            
            $file = $path;
            $path = "$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$newVersion.mo";
            $results = $this->compile($file,$path);
            foreach ($results as $filename => $status) {
              $output->writeln("$status: $filename");
            }
            
            if (!$input->getOption('keep-messages')) {
                unlink($file);
            }

            if ($version != $newVersion) {
                if (!file_put_contents($configFile, $newVersion)) {
                    $output->writeln("Version was not saved: " . $newVersion);
                }

                if (file_exists("$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$version.po")) {
                    unlink("$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$version.po");
                }
                if (file_exists("$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$version.mo")) {
                    unlink("$root/Resources/gettext/combined/$lang/LC_MESSAGES/messages$version.mo");
                }
            }

        }
        
        //http://www.gnu.org/software/gettext/manual/html_node/xgettext-Invocation.html
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
