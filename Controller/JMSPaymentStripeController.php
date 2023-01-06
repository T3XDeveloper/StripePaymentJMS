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
        session_start();
        $_SESSION['temp_customer'] = null;

        $stripe = new \Stripe\StripeClient($this->secretKey);
        $_SESSION['temp_customer'] = $stripe->customers->create([
            'description' => 'Temped Customer Object for Payment',
        ]);

        $intent = $stripe->paymentIntents->create(
            [
                'customer' => $_SESSION['temp_customer']->id,
                'setup_future_usage' => 'off_session',
                'amount' => intval($payment['amount']),
                'currency' => $payment['currency'],
                'automatic_payment_methods' => ['enabled' => true],
            ]
        );

        return $this->render(
            '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_interface.html.twig',
            [
                'intent' => $intent,
                'publicKey' => $this->apiKey
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
