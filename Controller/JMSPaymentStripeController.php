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
        $_SESSION['temp_customer'] = null;
        $_SESSION['temp_intent'] = null;

        $stripe = new \Stripe\StripeClient($this->secretKey);
        $_SESSION['temp_customer'] = $stripe->customers->create([
            'description' => 'Temped Customer Object for Payment',
        ]);

        $_SESSION['temp_intent'] = $stripe->paymentIntents->create(
            [
                'customer' => $_SESSION['temp_customer']->id,
                'setup_future_usage' => 'on_session',
                'amount' => intval($payment['amount']),
                'currency' => $payment['currency'],
                'automatic_payment_methods' => ['enabled' => true],
            ]
        );

        return $this->render(
            '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_methods.html.twig',
            [
                'intent' => $_SESSION['temp_intent'],
                'publicKey' => $this->apiKey
            ],
        );
    }

    #[Route('/_ajaxprocess/stripe')]
    public function renderPaymentIntent(): Response
    {
        if($request->query->get('variant')){
            return $this->render(
                '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_interface.html.twig',
                [
                    'intent' => $_SESSION['temp_intent'],
                    'publicKey' => $this->apiKey
                ],
            );
        } else {
            throw new \Exception();
        }
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
