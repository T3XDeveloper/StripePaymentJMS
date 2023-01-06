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
            __DIR__ . '/../Resources/Rendering/stripe_payment_interface.html.twig',
            [
                $payment,
                $this->gateway,
                $this->apiKey
            ],
        );
    }
}
