<?php
namespace OMX\Ecommerce\StripePackage\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/*
 * Copyright 2022 Patrick Wiermann <patrick@t3x-developer.de>
 */

class OMXEcommerceStripeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        $container->setParameter('payment.stripe.api_key', $config['api_key']);
        $container->setParameter('payment.stripe.debug', $config['debug']);
    }
}