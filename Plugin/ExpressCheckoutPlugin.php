<?php

namespace JMS\Payment\StripeBundle\Plugin;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Util\Number;
use Omnipay\Stripe\Gateway;

class ExpressCheckoutPlugin extends AbstractPlugin
{
    /**
     * @var \Omnipay\Stripe\Gateway
     */
    protected $gateway;

    public function __construct(
        Gateway $gateway, 
        $apiKey, 
        $secretKey
    )
    {
        $this->gateway = $gateway;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, 'Authorization');
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, 'Sale');
    }

    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        $approveTransaction = $transaction->getCredit()->getPayment()->getApproveTransaction();

        $parameters = [];
        if (Number::compare($transaction->getRequestedAmount(), $approveTransaction->getProcessedAmount()) !== 0) {
            $parameters['REFUNDTYPE'] = 'Partial';
            $parameters['AMT'] = $this->client->convertAmountToPaypalFormat($transaction->getRequestedAmount());
            $parameters['CURRENCYCODE'] = $transaction->getCredit()->getPaymentInstruction()->getCurrency();
        }
    }

    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        $authorizationId = $transaction->getPayment()->getApproveTransaction()->getReferenceNumber();

        if (Number::compare($transaction->getPayment()->getApprovedAmount(), $transaction->getRequestedAmount()) === 0) {
            $completeType = 'Complete';
        } else {
            $completeType = 'NotComplete';
        }
    }

    public function reverseApproval(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
    }

    public function processes($paymentSystemName)
    {
        return 'stripe_express_checkout' === $paymentSystemName;
    }

    public function isIndependentCreditSupported()
    {
        return false;
    }

    protected function createCheckoutBillingAgreement(FinancialTransactionInterface $transaction, $paymentAction)
    {
        $data = $transaction->getExtendedData();
        
        $stripe = new \Stripe\StripeClient($this->secretKey);
        $intent = $stripe->checkout->sessions->create([
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'line_items' => [
                [
                    'price' => 'price_H5ggYwtDq4fbrJ',
                    'quantity' => 2,
                ],
            ],
            'mode' => 'payment',
        ]);

        die(var_dump([
            $data,
            $intent
        ]));
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