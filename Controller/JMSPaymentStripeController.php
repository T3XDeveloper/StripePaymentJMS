<?php declare(strict_types=1);

namespace JMS\Payment\StripeBundle\Controller;

use Ibexa\Bundle\Commerce\Checkout\Entity\Basket;
use Ibexa\Bundle\Commerce\Eshop\Controller\AjaxController;
use Ibexa\Bundle\Commerce\Eshop\Exceptions\MaximumOrdersFailedException;
use Ibexa\Bundle\Commerce\Eshop\Services\WebConnectorErpService;
use Ibexa\Bundle\Commerce\Payment\Api\StandardPaymentService;
use Ibexa\Bundle\Commerce\Payment\Exception\MaxAmountExceededException;
use Ibexa\Bundle\Commerce\Payment\Exception\RedirectionRequiredException;
use Ibexa\Bundle\Commerce\Translation\Services\TransService;
use Ibexa\Bundle\Core\Controller as BaseController;
use Ibexa\Commerce\Checkout\Model\CheckoutSources;
use Ibexa\Commerce\Checkout\Service\BasketGuidService;
use Ibexa\Commerce\Checkout\Service\BasketService;
use JMS\Payment\CoreBundle\BrowserKit\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Ibexa\Bundle\Commerce\Checkout\Entity\BasketRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        $redirectUrl = '';
        $_SESSION['temp_customer'] = null;
        $_SESSION['temp_intent'] = null;
        $_SESSION['temp_payment'] = $payment;

        $basketID = $payment['basketId'];
        $basketSessionID = $payment['basketSessionId'];

        $stripe = new \Stripe\StripeClient($this->secretKey);
        $existCustomer = $stripe->customers->search([
            'query' => 'name:\''.$basketID.'&'.$basketSessionID.'\'',
        ]);

        if(isset($_GET['payment_intent']) && isset($_GET['payment_intent_client_secret']) && isset($_GET['redirect_status'])){
            $intent = $_GET['payment_intent'];
            $secret = $_GET['payment_intent_client_secret'];
            $status = $_GET['redirect_status'];

            $intentResponse = $stripe->paymentIntents->retrieve(
                $intent,
                []
            );

            if($intentResponse->client_secret == $secret && $intentResponse->status == $status){
                $basketRepository = $this->get(\Ibexa\Bundle\Commerce\Checkout\Entity\BasketRepository::class);
                $basket = $basketRepository->getBasketByBasketIdAndSessionId($basketID, $basketSessionID);

                $redirectUrl = $this->generateUrl(
                    'ibexa.commerce.checkout.confirmation',
                    ['basketId' => $basket->getBasketId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } else {
                throw new \Exception();
            }
        }

        if(!empty($existCustomer->data[0])){
            $_SESSION['temp_customer'] = $existCustomer->data[0];
        } else {
            $_SESSION['temp_customer'] = $stripe->customers->create([
                'description' => 'DXP Basket-ID: '.$basketID.'; DXP Basket-SessionID: '.$basketSessionID,
                'name' => $basketID.'&'.$basketSessionID
            ]);
        }

        $intentOptions = [
            'customer' => $_SESSION['temp_customer']->id,
            'amount' => round($payment['amount'], 2) * 100,
            'currency' => $payment['currency'],
            'automatic_payment_methods' => ['enabled' => true],
        ];

        $_SESSION['temp_intent'] = $stripe->paymentIntents->create(
            $intentOptions
        );

        return $this->render(
            '@ibexadesign/checkout/partials/formpartials/fields/stripe_payment_methods.html.twig',
            [
                'intent' => $_SESSION['temp_intent'],
                'publicKey' => $this->apiKey,
                'confirmation' => $redirectUrl
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

    /**
     * @Route("/_ajaxprocess/stripe-confirmation", name="payment_confirm")
     */
    public function confirmPaymentOrder()
    {
        if($_SESSION['temp_intent'] && $_SESSION['temp_customer']){
            $basketRepository = $this->get(\Ibexa\Bundle\Commerce\Checkout\Entity\BasketRepository::class);
            $basket = $basketRepository->getBasketByBasketIdAndSessionId($_GET['basketId'], $_GET['sessionId']);

            $stripe = new \Stripe\StripeClient($this->secretKey);
            $_SESSION['temp_intent'] = $stripe->paymentIntents->update(
                $_SESSION['temp_intent']->id,
                [
                    'description' => 'IBEXA Bestellnummer: '.$basket->getErpOrderId(),
                    'metadata' => [
                        'order_id' => $basket->getErpOrderId()
                    ]
                ]
            );
            return true;
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
