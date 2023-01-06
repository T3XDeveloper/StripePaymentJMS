<?php
namespace JMS\Payment\StripeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/*
 * Copyright 2022 Patrick Wiermann <patrick@t3x-developer.de>
 */

class JMSPaymentStripeExtension extends Extension
{
    public function prepend(ContainerBuilder $container)
    {
        $config = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/jms_payment_stripe.yml'));
        $container->prependExtensionConfig('jms_payment_stripe', $config);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        $container->setParameter('payment.stripe.api_key', $config['api_key']);
        $container->setParameter('payment.stripe.logger', $config['logger']);
        $container->setParameter('payment.stripe.methods', $config['methods']);
        $container->setParameter('payment.stripe.debug', $config['debug']);
    }

    private function prependJMSTranslation(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('jms_translation', [
            'configs' => [
                'ezcommerce_eshop' => [
                    'dirs' => [
                        __DIR__ . '/../',
                    ],
                    'output_dir' => __DIR__ . '/../Resources/translations/',
                    'output_format' => 'xliff',
                    'excluded_dirs' => [
                        'Behat',
                        'Tests',
                        'node_modules',
                        'Slot',
                        'Resources/public',
                        'Messages/Document',
                    ],
                    'ignored_domains' => ['FOSCommentBundle'],
                ],
            ],
        ]);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function prependBazingaJsTranslationConfiguration(ContainerBuilder $container): void
    {
        $configFile = __DIR__ . '/../Resources/config/bazinga_js_translation.yaml';
        $config = Yaml::parseFile($configFile);
        $container->prependExtensionConfig('bazinga_js_translation', $config);
        $container->addResource(new FileResource($configFile));
    }
}