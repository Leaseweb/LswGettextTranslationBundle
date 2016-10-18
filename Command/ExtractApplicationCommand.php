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
 * ExtractApplicationCommand extracts records to be translated from the current application
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class ExtractApplicationCommand extends AbstractCommand
{
    /**
     * Configures extractor
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:app:extract')
            ->setDescription('Extract translations from the application')
            ->setDefinition(array(
                new InputOption('keep-cache', null, InputOption::VALUE_NONE, 'Do not delete the intermediate twig.cache.php file'),
            ))
            ->setHelp(<<<EOT
The <info>gettext:app:extract</info> command extracts translations from the application:

  <info>php app/console gettext:app:extract</info>

You can keep the intermediate twig.cache.php file by specifying the keep-cache flag:

  <info>php app/console gettext:app:extract --keep-cache</info>

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
        chdir($root.'/..');
        $path = $root . $resourcesSubfolder . $messagesFile;
        $twig = $root . $resourcesSubfolder . 'twig.cache.php';
        $results = $this->convertTwigToPhp($twig, 'app');
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
        $results = $this->extractFromPhp($path, 'app');
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
    }
}
