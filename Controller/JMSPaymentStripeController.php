<?php declare(strict_types=1);

namespace JMS\Payment\StripeBundle\Controller;

use Ibexa\Bundle\Core\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Omnipay\Stripe\Gateway;

class JMSPaymentStripeController extends BaseController
{
    public function __construct(Gateway $gateway, $apiKey)
    {
        $this->gateway = $gateway;
        $this->apiKey = $apiKey;
    }

    public function requestPaymentmethods(array $payment): Response
    {
        return $this->render(
            '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_interface.html.twig',
            [
                $payment,
                $this->gateway,
                $this->apiKey
            ],
        );
    }

    public function setApiKey($value)
    {
        $this->apiKey = $value;
    }
}
