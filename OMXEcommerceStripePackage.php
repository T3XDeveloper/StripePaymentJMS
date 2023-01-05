<?php

namespace OMX\Ecommerce\StripePackage;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OMXEcommerceStripePackage extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);
    }
}
