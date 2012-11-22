<?php
namespace Lsw\GettextTranslationBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class GettextLocaleListener
{
  private $localeShortcuts;
  private $router;
  
  public function __construct($localeShortcuts, RouterInterface $router, $rootDir)
  {
    $this->localeShortcuts = $localeShortcuts;
    $this->router  = $router;
    $this->rootDir = rtrim($rootDir, '/');
  }
  
  public function onKernelRequest(GetResponseEvent $event)
  {
    if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType())
    {
      $context = $this->router->getContext();
      $current = setlocale(LC_MESSAGES, 0);
      if ($current && strpos($current,'.'))
      {
        list($current,$charset) = explode('.',$current);
      }
      /*$requested = isset($_SESSION['symfony/user/sfUser/culture'])
		    ? $_SESSION['symfony/user/sfUser/culture']
                    : $context->getParameter('_locale');*/
      $requested = $context->getParameter('_locale');
      if ($requested && strlen($requested)<5 && isset($this->localeShortcuts[$requested]))
      {
        $requested=$this->localeShortcuts[$requested];
      }
      if ($requested && $current != $requested)
      {
        $request = $event->getRequest();
        # ugly backwards compatibility fix for symfony 2.0 (start)
        if (!method_exists($request, 'setLocale')) $request = $request->getSession();
        # ugly backwards compatibility fix for symfony 2.0 (end)
        if (!setlocale(LC_MESSAGES, $requested.'.UTF-8', $requested.'.utf8', $requested.'.utf-8', $requested.'UTF8'))
        { 
          $request->setLocale(null);
          throw new InvalidParameterException("Requested locale '$requested' could not be set. Is this locale installed? Hint: Execute 'locale -a' on the Linux command line to list installed locales.");
        }
        $request->setLocale($requested);
      }

      $version = file_exists($this->rootDir.'/Resources/gettext/version')
                   ? file_get_contents($this->rootDir.'/Resources/gettext/version')
                   : "";
      
      // bind the default domain to the combined translations
      bindtextdomain('messages' . $version, $this->rootDir . '/Resources/gettext/combined/');
      textdomain('messages' . $version);
      bind_textdomain_codeset('messages' . $version, 'UTF-8');
    }
  }
}
