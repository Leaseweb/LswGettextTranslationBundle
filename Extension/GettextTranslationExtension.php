<?php
namespace Lsw\GettextTranslationBundle\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Util\Inflector;

class GettextTranslationExtension extends \Twig_Extension {
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
    $args[0] = gettext($msgid);
    return call_user_func_array('sprintf', $args);
  }

  public static function ngettext($msgid1, $msgid2, $n)
  {
    $args = func_get_args();
	array_splice($args, 0, 3, array(ngettext($msgid1, $msgid2, $n)));
    return call_user_func_array('sprintf', $args);
  }

  public function getName()
  {
    return 'lsw_gettext_translation_extension';
  }

}

