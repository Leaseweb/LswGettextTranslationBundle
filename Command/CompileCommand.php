<?php
/*
 * This file is part of the LswGettextTranslationBundle package.
 *
 * (c) LswGettextTranslationBundle <http://leaseweb.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ExtractApplicationCommand extracts records to be translated from the current application
 *
 * @author Marc Casòliva <marc@casoliva.cat>
 */
class CompileCommand extends AbstractCommand
{
    /**
     * Configure
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:compile')
            ->setDescription('Compiles translations from .po to .mo for specific languages')
            ->setDefinition(array(
                new InputArgument('languages', InputArgument::REQUIRED, 'The language list')
            ))
            ->setHelp(<<<EOT
The <info>gettext:combine</info> command combines translations from all
bundles and the application for specific languages:

  <info>php app/console gettext:compile</info>

This interactive shell will ask you for a language list.

You can alternatively specify the comma-separated language list as the first argument:

  <info>php app/console gettext:compile en_US,nl_NL,de_DE</info>

EOT
            );
    }

    /**
     * Execute method get an input texts prepare it for each locale
     *
     * @param InputInterface $input Input interface
     * @param OutputInterface $output Output interface
     *
     * @see Command
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $root = $container->getParameter('kernel.root_dir');
        $resourcesSubfolder = $container->getParameter('lsw_gettext_resources_subfolder');
        chdir($root . '/..');

        $configFile = $root . $resourcesSubfolder . "version";
        $languages  = explode(',', trim($input->getArgument('languages'), ','));

        foreach ($languages as $lang) {
            $lang = trim($lang);
            $file = $root . $resourcesSubfolder . $lang . '/LC_MESSAGES/messages.po';
            if (!file_exists($file)) {
                $output->writeln("File does not exist: " . $file);
                exit(-1);
            }

            $path = $root . $resourcesSubfolder . $lang . '/LC_MESSAGES/messages.mo';
            $results = $this->compile($file, $path);
            foreach ($results as $filename => $status) {
                $output->writeln("$status: $filename");
            }
        }
        //http://www.gnu.org/software/gettext/manual/html_node/xgettext-Invocation.html
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
        if (!$input->getArgument('languages')) {
            $languages = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please enter the list of languages (comma seperated):',
                function ($languages) {
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
