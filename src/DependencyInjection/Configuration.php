<?php

namespace Athenea\MonologBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('athenea_monolog');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('mongo_log_collection')->defaultValue('athenea_monolog_logs')->end()
                ->scalarNode('mongo_deprecation_log_collection')->defaultValue('athenea_monolog_deprecation_logs')->end()
                ->booleanNode('send_log_mails')->defaultValue(false)->end()
                ->scalarNode('app_name')->defaultValue('APP')->end()
                ->scalarNode('email_from')->defaultValue('apps@atheneasolutions.com')->end()
                ->variableNode('email_recipients') // Accepts any value, including strings, arrays, etc.
                    ->defaultValue([])
                ->end()
            ->end();
    
        return $treeBuilder;
    }
}
