<?php

namespace Lsw\GettextTranslationBundle\DependencyInjection;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * Gettext translation extension
 *
 */
class LswGettextTranslationExtension extends Extension
{

    /**
     * Adds classes to compiler
     *
     * @param array $classes classes
     */
    public function addClassesToCompile(array $classes)
    {
        // TODO: Auto-generated method stub

    }

    /**
     * Loads services.yml and config.yml to a container
     *
     * @param array $configs Configs array
     * @param ContainerBuilder $container Container builder
     *
     * @throws Exception
     * @see \Symfony\Component\DependencyInjection\Extension\ExtensionInterface::load()
     */
  public function load(array $configs, ContainerBuilder $container)
  {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('services.yml');
    $loader->load('config.yml');
  }

  /**
   * Returns an alias for gettext translation extension
   *
   * @see \Symfony\Component\HttpKernel\DependencyInjection\Extension::getAlias()
   * @return string
   */
  public function getAlias()
  {
    return 'lsw_gettext_translation';
  }
}
