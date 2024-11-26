<?php

namespace Athenea\MonologBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AtheneaMonologExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Arguments
        $definition = $container->getDefinition("athenea.monolog.activation_strategy.param_based_activation");
        $definition->replaceArgument('$enabled', $config['send_log_mails']);

        $container->setParameter('athenea.monolog.email_recipients', $config['email_recipients']);
        $container->setParameter('athenea.monolog.email_from', $config['email_from']);
        $container->setParameter('athenea.monolog.mongo_log_collection', $config['mongo_log_collection']);
        $container->setParameter('athenea.monolog.mongo_deprecation_log_collection', $config['mongo_deprecation_log_collection']);
        $container->setParameter('athenea.monolog.app_name', $config['app_name']);
    }
}
