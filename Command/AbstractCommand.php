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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Contains common command functions for the gettext Bundle commands
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * Search for files with specific extension
     * @param string $dir
     * @param string $extension
     * @return array
     */
    protected function findFilesInFolder($dir, $extension)
    {
        $templates = array();
        if (is_dir($dir)) {
            $finder = new Finder();
            foreach ($finder->files()->followLinks()->in($dir)->exclude('cache')->name('*.' . $extension) as $file) {
                $templates[] = $this->relative($file->getPathname());
            }
        }

        return $templates;
    }

    /**
     * Get relative path from file to directory
     * @param string $from
     * @param string $to
     * @param string $ps
     * @return string
     */
    private function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $from = realpath($from);
        $to = realpath($to);
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }
        
        return str_pad("", count($arFrom) * 3, '..' . $ps) . implode($ps, $arTo);
    }
    
    /**
     * Get relative path from current working directory to destination folder
     * @param string $to
     * @return string
     */
    protected function relative($to)
    { 
        return $this->relativePath('.', $to);
    }
    
    /**
     * Convert *.twig files within specified directory to *.php for further extraction of translation text
     * @param string $path
     * @param string $name
     * @return array
     * @throws \Exception 
     */
    protected function convertTwigToPhp($path, $name)
    {
        $results = array();
        
        if (!file_exists(dirname($path))) {
          mkdir(dirname($path), 0755, true);
        }
        
        $templates = $this->findFilesInFolder(dirname($path) . '/../views', 'twig');
               
        $php  = "<?php\n";
        $twig = $this->getContainer()->get('twig');
        $twig->setLoader(new \Twig_Loader_String());
        foreach ($templates as $templateFileName) {
            $stream = $twig->tokenize(file_get_contents($templateFileName));
            $nodes = $twig->parse($stream);
            $template = $twig->compile($nodes);
            $template = explode("\n",$template);
            array_shift($template);
            $template = implode("\n",$template);
            $php .= "/*\n * Resource: $name\n * File: $templateFileName\n */\n";
            $php .= $template;
            $results[$templateFileName]='Scanned';
        }
        
        if (!file_put_contents($path, $php)) {
            throw new \Exception('Cannot write intermediate PHP code for twig templates to twig.cache.php in: '.$path);
        }
        
        return $results;
    }
    
    /**
     * Extract translation strings from all *.php files within the given path
     * @param string $path
     * @return string
     * @throws \Exception 
     */
    protected function extractFromPhp($path)
    {
        $results = array();
        
        // check the path exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        // clean .tmp file for further using it as cache
        if (file_exists("$path.tmp"))  {
            unlink("$path.tmp");
        }
        
        // find all *.php files in the given directory
        $files = $this->findFilesInFolder(dirname($path) . '/../..', 'php');
        
        // define options for finding translation strings within *.php files
        $options = implode(' ',array(
            '--keyword=__:1',
            '--keyword=__n:1,2',
            '--keyword=_:1',
            '--keyword=_n:1,2',
            '--from-code=UTF-8',
            '--force-po',
            '-L PHP',
            '-f -',
            "-o \"$path.tmp\"",
        ));
        
        $descriptors = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w'),  // stderr
        );
        $process = proc_open('xgettext '.$options, $descriptors, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0],implode("\n", $files));
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $output = stream_get_contents($pipes[2]);
            $return = proc_close($process);
        }
        
        if ($return != 0 && $output) {
            throw new \Exception($output);
        }
        if ($output) {
            echo "Warning: $output\n";
        }
        if (!file_exists("$path.tmp")) {
            throw new \Exception('xgettext failed extracting messages for translating. Did you install gettext?');
            // tell about windows: http://www.gtk.org/download/win32.php
        }
        rename("$path.tmp", $path);
        foreach ($files as $filename) {
            $results[$filename] = 'Scanned';
        }
        $results[$this->relative($path)] = 'Written';
        
        return $results;
    }
    
    /**
     * Initialize .po file from template
     * @param string $template
     * @param string $languages
     * @return array
     * @throws ResourceNotFoundException 
     */
    protected function initializeFromTemplate($template, $languages)
    {
        $results = array();
        $target = dirname($template);
        if(!file_exists($template)) {
            throw new ResourceNotFoundException("Template not found in: $template\n\nRun 'app/console gettext:bundle:extract' first.");
        }
        $results[$this->relative($template)] = 'Scanned';
        $languages = explode(',', trim($languages));
        foreach ($languages as $lang) {
            $lang = trim($lang);
            $file = "$target/locale/$lang/LC_MESSAGES/messages.po";
            if (file_exists($file)) {
                $results[$this->relative($file)] = 'Skipped';
                continue;
            }
            if (!file_exists(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            $data = file_get_contents($template);
            $version = $this->relative($target . '/../..') . '@' . date('YmdHis');
            $data = preg_replace('/Project-Id-Version: PACKAGE VERSION\\\n/', 'Project-Id-Version: ' . $version . '\n', $data, 1);
            $data = preg_replace('/Language: \\\n/','Language: ' . $lang . '\n', $data, 1);
            $data = preg_replace('/charset=CHARSET/', 'charset=UTF-8', $data, 1);
            $status = file_put_contents($file, $data) ? 'Created' : 'Failed';
            $results[$this->relative($file)] = $status;
        }
        return $results;
    }
    
    /**
     * Combine list of .po files into one
     * @param array $files
     * @param string $path
     * @return string
     * @throws \Exception 
     */
    protected function combineFiles(array $files, $path)
    {
        // initialize result variable
        $results = array();
        
        // create source directory if it doesn't exist
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        // clean .tmp file for further using it as cache
        if (file_exists("$path.tmp")) {
            unlink("$path.tmp");
        }
        
        // define options for finding translation strings within *.php files
        $options = implode(' ',array(
            '--use-first',
            '--force-po',
            '-f -',
            "-o $path.tmp",
        ));
        $descriptors = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );
        $process = proc_open('msgcat ' . $options, $descriptors, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0],implode("\n", $files));
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $output = stream_get_contents($pipes[2]);
            $return = proc_close($process);
        }
        if ($return != 0 && $output) {
            throw new \Exception($output);
        }
        if (!file_exists("$path.tmp")) {
            throw new \Exception('msgcat failed concatenating messages for translating. Did you install gettext?');
        }
        rename("$path.tmp", $path);
        foreach ($files as $filename) {
            $results[$filename] = 'Scanned';
        }
        $results[$this->relative($path)] = 'Written';
        
        return $results;
    }
    
    /**
     * Compile message catalog to binary format
     * @param string $file
     * @param string $path
     * @return array
     * @throws \Exception 
     */
    protected function compile($file,$path)
    {
        $results = array();
        
        // if .tmp file exists, clean it for further using it as cache
        if (file_exists("$path.tmp")) {
            unlink("$path.tmp");
        }
        
        $options = implode(' ',array(
            '--check',
            "-o $path.tmp",
            $file,
        ));
        
        $descriptors = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );
        
        $process = proc_open('msgfmt '.$options, $descriptors, $pipes);
        if (is_resource($process)) {
            stream_get_contents($pipes[1]);
            $output = stream_get_contents($pipes[2]);
            $return = proc_close($process);
        }
        if ($return!=0 && $output) {
            throw new \Exception($output);
        }
        if (!file_exists("$path.tmp")) {
            throw new \Exception('msgfmt failed to compile messages for translating. Did you install gettext?');
        }
        rename("$path.tmp", $path);
        $results[$this->relative($file)] = 'Scanned';
        $results[$this->relative($path)] = 'Written';
        
        return $results;
    }
}
