<?php declare(strict_types=1);

namespace JMS\Payment\StripeBundle\Controller;

use Ibexa\Bundle\Core\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Ibexa\Bundle\Commerce\Checkout\Entity\BasketRepository;

/**
 * @Route("/")
 */
class JMSPaymentStripeController extends BaseController
{
    public function __construct(
        $apiKey,
        $secretKey,
        $basketRepository
    )
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->basketRepository = $basketRepository;
    }

    public function requestPaymentmethods(array $payment): Response
    {
        $_SESSION['temp_customer'] = null;
        $_SESSION['temp_intent'] = null;
        $_SESSION['temp_payment'] = $payment;

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
                'amount' => round($payment['amount'], 2) * 100,
                'currency' => $payment['currency'],
                'shipping' => [
                    'name' => $payment['userName'],
                    'address' => [
                        'country' => $payment['userCountry']
                    ]
                ],
                'receipt_email' => $payment['userEmail'],
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
            $basketRepository = $this->get(\Ibexa\Bundle\Commerce\Checkout\Entity\BasketRepository::class);
            $basket = $basketRepository->getBasketByBasketIdAndSessionId($_SESSION['temp_payment']['basketId'], $_SESSION['temp_payment']['basketSessionId']);

            $stripe = new \Stripe\StripeClient($this->secretKey);
            $_SESSION['temp_intent'] = $stripe->paymentIntents->update(
                $_SESSION['temp_intent']->id,
                ['amount' => round($basket->getTotalsSumGross(), 2) * 100]
            );

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

    public function setBasketRespository($value)
    {
        $this->basketRespository = $value;
    }
}
