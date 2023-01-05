<?php

namespace OMX\Ecommerce\StripePackage;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Yaml;

class OMXEcommerceStripePackage extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        $config = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/jms_payment_stripe.yml'));
        $builder->prependExtensionConfig('ommax_ecommerce_stripe', $config);

        parent::build($builder);
    }
}
