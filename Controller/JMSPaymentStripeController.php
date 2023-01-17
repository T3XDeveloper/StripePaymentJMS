<?php declare(strict_types=1);

namespace JMS\Payment\StripeBundle\Controller;

use Ibexa\Bundle\Core\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
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

        $basketID = $payment['basketId'];
        $basketSessionID = $payment['basketSessionId'];

        $stripe = new \Stripe\StripeClient($this->secretKey);
        $existCustomer = $stripe->customers->search([
            'query' => 'name:\''.$basketID.'&'.$basketSessionID.'\'',
        ]);

        if(!empty($existCustomer->data[0])){
            $_SESSION['temp_customer'] = $existCustomer->data[0];
        } else {
            $_SESSION['temp_customer'] = $stripe->customers->create([
                'description' => 'DXP Basket-ID: '.$basketID.'; DXP Basket-SessionID: '.$basketSessionID,
                'name' => $basketID.'&'.$basketSessionID
            ]);
        }

        $_SESSION['temp_intent'] = $stripe->paymentIntents->create(
            [
                'customer' => $_SESSION['temp_customer']->id,
                'setup_future_usage' => 'on_session',
                'amount' => $payment['amount'] * 100,
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

    /**
     * @Route("/_ajaxprocess/stripe", name="payment_add")
     */
    public function renderPaymentIntent(): Response
    {
        if($_SESSION['temp_intent'] && $_SESSION['temp_customer']){
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
