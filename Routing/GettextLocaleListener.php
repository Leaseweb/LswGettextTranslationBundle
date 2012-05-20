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
  
  public function __construct($localeShortcuts, RouterInterface $router)
  {
    $this->localeShortcuts = $localeShortcuts;
    $this->router = $router;
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
      $requested = $context->getParameter('_locale');
      if ($requested && strlen($requested)<5 && isset($this->localeShortcuts[$requested]))
      {
        $requested=$this->localeShortcuts[$requested];
      }
      if ($requested && $current != $requested)
      { 
        
        if (!setlocale(LC_MESSAGES, $requested.'.UTF-8'))
        { 
          if ($event->getRequest()->getSession()->getLocale())
          {
            $event->getRequest()->getSession()->setLocale(null);
          }
          throw new InvalidParameterException("Requested locale '$requested' could not be set. Is this locale installed? Hint: Execute 'locale -a' on the Linux command line to list installed locales.");
        }
      }
    }
  }
}