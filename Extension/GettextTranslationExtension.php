<?php
namespace Lsw\GettextTranslationBundle\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Util\Inflector;

class GettextTranslationExtension extends \Twig_Extension {
  
  private $container;
  private $generator;
  
  public function __construct(UrlGeneratorInterface $generator,Container $container)
  {
    $this->generator = $generator;
    $this->container = $container;
  }
  
  public function getFunctions()
  {
    return array(
      '_' => new \Twig_Function_Function('gettext'),
      '_n' => new \Twig_Function_Function('ngettext'),
      '__' => new \Twig_Function_Method($this, 'gettext'),
      '__n' => new \Twig_Function_Method($this, 'ngettext'),
    );
  }
  
  public static function gettext($msgid)
  {
    $args = func_get_args();
    $msgid = array_shift($args);
    $str = gettext($msgid);
    array_unshift($args, $str);
    return call_user_func_array('sprintf', $args);
  }

  public static function ngettext($msgid1, $msgid2, $n)
  {
    $args = func_get_args();
    $msgid1 = array_shift($args);
    $msgid2 = array_shift($args);
    $n = array_shift($args);
    $str = ngettext($msgid1, $msgid2, $n);
    array_unshift($args, $str);
    return call_user_func_array('sprintf', $args);
  }

  public function getName()
  {
    return 'lsw_gettext_translation_extension';
  }

}

