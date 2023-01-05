<?php
namespace OMX\Ecommerce\StripePackage\DependencyInjection;

use JMS\Payment\CoreBundle\Entity\ExtendedDataType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

/*
 * Copyright 2022 Patrick Wiermann <patrick@t3x-developer.de>
 */

class OMXEcommerceStripeExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    ExtendedDataType::NAME => 'JMS\Payment\CoreBundle\Entity\ExtendedDataType',
                ],
            ],
        ]);
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        if (version_compare(Kernel::VERSION, '3.0', '>=')) {
            $xmlLoader->load('commands.xml');
        }

        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $container->setParameter('ommax_ecommerce_stripe.api_key', $config['api_key']);

        if ($config['methods']) {
            foreach($config['methods'] AS $method) {
                $this->addFormType($container, $method);
            }
        }
        if (false === $config['logger']) {
            $container->getDefinition('ommax_ecommerce_stripe.plugins.credit_card')->removeMethodCall('setLogger');
            $container->removeDefinition('monolog.logger.ommax_ecommerce_stripe');
        }
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * @param ContainerBuilder $container
     * @param mixed            $method
     */
    protected function addFormType(ContainerBuilder $container, $method)
    {
        $stripeMethod = 'stripe_' . $method;

        $definition = new Definition();
        if($container->hasParameter(sprintf('ommax_ecommerce_stripe.form.%s_type.class', $method))) {
            $definition->setClass(sprintf('%%ommax_ecommerce_stripe.form.%s_type.class%%', $method));
        } else {
            $definition->setClass('%ommax_ecommerce_stripe.form.stripe_type.class%');
        }
        $definition->addArgument($stripeMethod);

        $definition->addTag('payment.method_form_type');
        $definition->addTag('form.type', array(
            'alias' => $stripeMethod
        ));

        $container->setDefinition(
            sprintf('ommax_ecommerce_stripe.form.%s_type', $method),
            $definition
        );
    }
}