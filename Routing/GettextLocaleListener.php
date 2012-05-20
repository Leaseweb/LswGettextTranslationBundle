<?php
namespace Lsw\GettextTranslationBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class GettextLocaleListener
{
  private $router;
  
  public function __construct(RouterInterface $router)
  {
    $this->router = $router;
  }
  
  public function onKernelRequest(GetResponseEvent $event)
  {
    if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType())
    {
      $context = $this->router->getContext();
      $current = setlocale(LC_MESSAGES, 0);
      $requested = $context->getParameter('_locale');
      if ($current != $requested)
      { 
        if (!setlocale(LC_MESSAGES, $requested))
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