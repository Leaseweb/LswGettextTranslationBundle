<?php
namespace
{
  // NB: function '_()' is already defined and points to 'gettext()', this is PHP standard behavior
  
  function _n()
  {
    return call_user_func_array('ngettext', func_get_args());
  }
  
  function __()
  {
    return call_user_func_array('Lsw\GettextTranslationBundle\Extension\GettextTranslationExtension::gettext', func_get_args());
  }
  
  function __n()
  {
    return call_user_func_array('Lsw\GettextTranslationBundle\Extension\GettextTranslationExtension::ngettext', func_get_args());
  }

  // bind the default domain to the combined translations
  bindtextdomain('messages','../app/Resources/gettext/combined');
  bind_textdomain_codeset('messages', 'UTF-8');
}


namespace Lsw\GettextTranslationBundle
{
  
  use Symfony\Component\HttpKernel\Bundle\Bundle;
  
  class LswGettextTranslationBundle extends Bundle
  {
  }

}