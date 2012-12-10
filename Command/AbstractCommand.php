<?php

namespace Lsw\GettextTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * AbstractCommand contains command functions for the gettext Bundle commands
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
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

    protected function relative($to)
    { 
        return $this->relativePath('.', $to);
    }
    
    protected function convertTwigToPhp($path, $name)
    {
        $results = array();

        $dir = dirname($path);

        if (!file_exists($dir)) {
          mkdir($dir, 0755, true);
        }

        $templates = $this->findFilesInFolder($dir . '/../views', 'twig');

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
    
    protected function extractFromPhp($path)
    {
        $results = array();

        $dir = dirname($path);

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        } else if (file_exists("$path.tmp"))  {
            unlink("$path.tmp");
        }

        $files = $this->findFilesInFolder($dir . '/../..', 'php');

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
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
        );
        $process = proc_open('xgettext '.$options, $descriptors, $pipes);
        if (is_resource($process)) {
          fwrite($pipes[0],implode("\n", $files));
          fclose($pipes[0]);
          stream_get_contents($pipes[1]);
          $output = stream_get_contents($pipes[2]);
          $return = proc_close($process);
        }
        if ($return!=0 && $output) {
          throw new \Exception($output);
        }
        if ($output) echo "Warning: $output\n";
        if (!file_exists("$path.tmp")) {
            throw new \Exception('xgettext failed extracting messages for translating. Did you install gettext?');
            // tell about windows: http://www.gtk.org/download/win32.php
        }
        rename("$path.tmp", $path);

        $results = array();
        foreach ($files as $filename) $results[$filename] = 'Scanned';
        $results[$this->relative($path)]='Written';
        return $results;
    }
    
    protected function initializeFromTemplate($template,$languages)
    {
        $results = array();
        $target = dirname($template);
        if(!file_exists($template)) {
            throw new ResourceNotFoundException("Template not found in: $template\n\nRun 'app/console gettext:bundle:extract' first.");
        }
        $results[$this->relative($template)]='Scanned';
        $languages = explode(',', trim($languages));
        foreach ($languages as $lang) {
            $lang = trim($lang);
            $file = "$target/locale/$lang/LC_MESSAGES/messages.po";
            if (file_exists($file)) {
                $results[$this->relative($file)]='Skipped';
                continue;
            }
            if (!file_exists(dirname($file))) {
                mkdir(dirname($file),0755,true);
            }
            $data = file_get_contents($template);
            $version = $this->relative($target.'/../..').'@'.date('YmdHis');
            $data = preg_replace('/Project-Id-Version: PACKAGE VERSION\\\n/','Project-Id-Version: '.$version.'\n',$data,1);
            $data = preg_replace('/Language: \\\n/','Language: '.$lang.'\n',$data,1);
            $data = preg_replace('/charset=CHARSET/','charset=UTF-8',$data,1);
            $status = file_put_contents($file, $data)?'Created':'Failed';
            $results[$this->relative($file)] = $status;
        }
        return $results;
    }
    
    protected function combineFiles($files,$path)
    {
        $results = array();
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        } else if (file_exists("$path.tmp")) {
            unlink("$path.tmp");
        }
        $options = implode(' ',array(
            '--use-first',
            '--force-po',
            '-f -',
            "-o $path.tmp",
        ));
        $descriptors = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
        );
        $process = proc_open('msgcat '.$options, $descriptors, $pipes);
        if (is_resource($process)) {
          fwrite($pipes[0],implode("\n", $files));
          fclose($pipes[0]);
          stream_get_contents($pipes[1]);
          $output = stream_get_contents($pipes[2]);
          $return = proc_close($process);
        }
        if ($return!=0 && $output) {
          throw new \Exception($output);
        }
        if (!file_exists("$path.tmp")) {
            throw new \Exception('msgcat failed concatenating messages for translating. Did you install gettext?');
        }
        rename("$path.tmp", $path);

        $results = array();
        foreach ($files as $filename) $results[$filename] = 'Scanned';
        $results[$this->relative($path)]='Written';
        return $results;
    }
    
    protected function compile($file,$path)
    {
        $results = array();
        if (file_exists("$path.tmp")) {
            unlink("$path.tmp");
        }
        $options = implode(' ',array(
            '--check',
            "-o $path.tmp",
            $file,
        ));
        $descriptors = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
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
        $results[$this->relative($file)]='Scanned';
        $results[$this->relative($path)]='Written';
        return $results;
    }

    // FIXME: share this code with Symfony (File::getRelativePath(Dir))
    private function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $from = realpath($from);
        $to = realpath($to);

        $equalOffset = 0;
        $minLength = min(strlen($from), strlen($to));

        while ($equalOffset < $minLength && $from[$equalOffset] == $to[$equalOffset])
            $equalOffset++;

        $backCount =
            $equalOffset == $minLength && strlen($from) < strlen($to)
                ? 0
                : substr_count($from, $ps, $equalOffset-1);

        return rtrim(str_repeat('..'.$ps, $backCount).ltrim(substr($to, $equalOffset), $ps), $ps);
    }

}
