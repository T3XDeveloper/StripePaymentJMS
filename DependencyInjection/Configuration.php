<?php
namespace JMS\Payment\StripeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/*
 * Copyright 2022 Patrick Wiermann <patrick@t3x-developer.de>
 */

class Configuration implements ConfigurationInterface
{
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder($this->alias, 'array');

        $tb
            ->getRootNode()
                ->children()
                    ->scalarNode('api_key')->defaultNull()->end()
                    ->scalarNode('secret_key')->defaultNull()->end()
                    ->arrayNode('methods')
                        ->scalarPrototype()->end()
                    ->end()
                    ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->end();

        return $tb;
    }
}