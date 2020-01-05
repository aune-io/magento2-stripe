<?php

namespace Aune\Stripe\Gateway\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Model\Adapter\StripeAdapter;

class PaymentIntentProvider
{
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
     * @var StripeAdapter
     */
    private $stripeAdapter;

    /**
     * @param Config $config
     * @param AmountProvider $amountProvider
     * @param StripeAdapter $stripeAdapter
     */
    public function __construct(
        Config $config,
        AmountProvider $amountProvider,
        StripeAdapter $stripeAdapter
    ) {
        $this->config = $config;
        $this->amountProvider = $amountProvider;
        $this->stripeAdapter = $stripeAdapter;
    }

    /**
     * Return a Payment Intent for the quote, taking care of creating or updating it
     */
    public function getPaymentIntent(Quote $quote, $publicHash = null)
    {
        $currency = $quote->getBaseCurrencyCode();
        $amount = $quote->getBaseGrandTotal();

        // Create new Stripe Payment Intent
        //@todo: rewrite as command to re-use for vaulted payment methods
        $params = [
            'currency' => $currency,
            'amount' => $this->amountProvider->convert($amount, $currency),
            'capture_method' => $this->getCaptureMethod(),
        ];

        if (!is_null($publicHash)) {
            // Use vaulted payment method
            $params['customer'] = 'cus_GUGes1omIq5nUx';
            $params['payment_method'] = 'pm_1FxJq4D7OORb2ZnPbYSMMp7R';
        } elseif ($this->config->isStoreCustomerEnabled()) {
            // Vault for future usage if not already vaulted, and if enabled
            $params['setup_future_usage'] = $this->config->getStoreFutureUsage();
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
