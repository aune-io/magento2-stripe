<?php

namespace Aune\Stripe\Gateway\Command;

use Stripe\Customer;

use Magento\Framework\Exception\PaymentException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Model\Adapter\StripeAdapter;

class CreatePaymentIntentCommand implements CommandInterface
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const CUSTOMER = 'customer';
    const PAYMENT_METHOD = 'payment_method';
    const CAPTURE_METHOD = 'capture_method';
    const SETUP_FUTURE_USAGE = 'setup_future_usage';

    const CAPTURE_METHOD_AUTOMATIC = 'automatic';
    const CAPTURE_METHOD_MANUAL = 'manual';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AmountProvider
     */
    private $amountProvider;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    /**
     * @var StripeAdapter
     */
    private $stripeAdapter;

    /**
     * @param Config $config
     * @param AmountProvider $amountProvider
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param TokenProvider $tokenProvider
     * @param StripeAdapter $stripeAdapter
     */
    public function __construct(
        Config $config,
        AmountProvider $amountProvider,
        PaymentTokenManagementInterface $tokenManagement,
        TokenProvider $tokenProvider,
        StripeAdapter $stripeAdapter
    ) {
        $this->config = $config;
        $this->amountProvider = $amountProvider;
        $this->tokenManagement = $tokenManagement;
        $this->tokenProvider = $tokenProvider;
        $this->stripeAdapter = $stripeAdapter;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        $quote = $commandSubject['quote'];
        $currency = $quote->getBaseCurrencyCode();
        $amount = $quote->getBaseGrandTotal();

        $params = [
            self::CURRENCY => $currency,
            self::AMOUNT => $this->amountProvider->convert($amount, $currency),
            self::CAPTURE_METHOD => $this->getCaptureMethod(),
        ];

        if (!empty($commandSubject['public_hash'])) {

            // Use vaulted payment method
            $publicHash = $commandSubject['public_hash'];
            $customerId = $commandSubject['customer_id'];

            $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);
            if (!$paymentToken) {
                throw new PaymentException('Invalid payment token');
            }

            $params[self::CUSTOMER] = $this->tokenProvider->getCustomerStripeId($customerId);
            $params[self::PAYMENT_METHOD] = $paymentToken->getGatewayToken();

        } else if ($this->config->isStoreCustomerEnabled()) {
            // Setup for future usage if vaulting is enabled
            // and the payment method isn't vaulted already
            $params[self::SETUP_FUTURE_USAGE] = $this->config->getStoreFutureUsage();
        }

        return $this->stripeAdapter->paymentIntentCreate($params);
    }

    /**
     * Return the capture method, based on configuration
     */
    private function getCaptureMethod()
    {
        $paymentAction = $this->config->getPaymentAction();

        return $paymentAction === AbstractMethod::ACTION_AUTHORIZE ?
            self::CAPTURE_METHOD_MANUAL : self::CAPTURE_METHOD_AUTOMATIC;
    }
}