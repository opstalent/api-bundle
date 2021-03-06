<?php

namespace Opstalent\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class OpstalentApiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $container->setParameter( 'opstalent_api.generator.ignore', $config['generator']['ignore'] );
        $loader->load('services.yml');

        $normalizerDef = $container->getDefinition('opstalent.api_bundle.normalizer.datetime');
        $normalizerDef->setArguments([
            $config['serializer']['normalizer']['datetime']['datetime_format']
        ]);
    }
}
