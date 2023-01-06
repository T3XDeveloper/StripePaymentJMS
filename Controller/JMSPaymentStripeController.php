<?php declare(strict_types=1);

namespace JMS\Payment\StripeBundle\Controller;

use Ibexa\Bundle\Core\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class JMSPaymentStripeController extends BaseController
{
    public function __construct($apiKey, $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    public function requestPaymentmethods(array $payment): Response
    {
        $stripe = new \Stripe\StripeClient($this->secretKey);
        $intent = $stripe->paymentIntents->create(
            [
                'amount' => 1099,
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
            ]
        );

        return $this->render(
            '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_interface.html.twig',
            [
                $payment,
                $intent,
                $this->apiKey
            ],
        );
    }

    public function setApiKey($value)
    {
        $this->apiKey = $value;
    }

    public function setSecretKey($value)
    {
        $this->secretKey = $value;
    }
}
