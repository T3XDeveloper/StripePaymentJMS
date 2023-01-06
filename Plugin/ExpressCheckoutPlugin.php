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

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function processes($paymentSystemName)
    {
        return $paymentSystemName === 'stripe_express_checkout';
    }

    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, $retry);
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, $retry);
    }

    protected function createCheckoutBillingAgreement(FinancialTransactionInterface $transaction, $retry)
    {
        $parameters = $this->getPurchaseParameters($transaction);
        $response = $this->gateway->purchase($parameters)->send();

        if($response->isSuccessful()) {
            $transaction->setReferenceNumber($response->getTransactionReference());

            $data = $response->getData();

            $transaction->setProcessedAmount($data['amount'] / 100);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);

            return;
        }

        $data = $response->getData();
        switch($data['error']['type']) {
            case "api_error":
                $ex = new FinancialException($response->getMessage());
                $ex->addProperty('error', $data['error']);
                $ex->setFinancialTransaction($transaction);

                $transaction->setResponseCode('FAILED');
                $transaction->setReasonCode($response->getMessage());
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                break;

            case "card_error":
                $ex = new FinancialException($response->getMessage());
                $ex->addProperty('error', $data['error']);
                $ex->setFinancialTransaction($transaction);

                $transaction->setResponseCode('FAILED');
                $transaction->setReasonCode($response->getMessage());
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                break;

            default:
                $ex = new FinancialException($response->getMessage());
                $ex->addProperty('error', $data['error']);
                $ex->setFinancialTransaction($transaction);

                $transaction->setResponseCode('FAILED');
                $transaction->setReasonCode($response->getMessage());
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                break;
        }

        throw $ex;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return array
     */
    protected function getPurchaseParameters(FinancialTransactionInterface $transaction)
    {
        /**
         * @var \JMS\Payment\CoreBundle\Model\PaymentInterface $payment
         */
        $payment = $transaction->getPayment();

        /**
         * @var \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface $paymentInstruction
         */
        $paymentInstruction = $payment->getPaymentInstruction();

        /**
         * @var \JMS\Payment\CoreBundle\Model\ExtendedDataInterface $data
         */
        $data = $transaction->getExtendedData();

        $transaction->setTrackingId($payment->getId());

        $parameters = array(
            'amount'      => $payment->getTargetAmount(),
            'currency'    => $paymentInstruction->getCurrency(),
            'description' => ($data->get('description') ? $data->get('description') : 'not set'),
            'token'       => $data->get('token'),
        );

        return $parameters;
    }
}