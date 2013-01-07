<?php
namespace
{
    // NB: function '_()' is already defined and points to 'gettext()', this is PHP standard behavior

    /**
     * Alias for 'ngettext'
     *
     * @return string
     */
    function _n()
    {
        return call_user_func_array('ngettext', func_get_args());
    }

    /**
     * Alias for 'gettext' that takes 'sprintf' arguments
     *
     * @return string
     */
    function __()
    {
        return call_user_func_array('Lsw\GettextTranslationBundle\Extension\GettextTranslationExtension::gettext', func_get_args());
    }

    /**
     * Alias for 'ngettext' that takes 'sprintf' arguments
     *
     * @return string
     */
    function __n()
    {
        return call_user_func_array('Lsw\GettextTranslationBundle\Extension\GettextTranslationExtension::ngettext', func_get_args());
    }
}

namespace Lsw\GettextTranslationBundle
{

    use Symfony\Component\HttpKernel\Bundle\Bundle;

    /**
     * Bundle to add PHP gettext support
     */
    class LswGettextTranslationBundle extends Bundle
    {
    }

}
