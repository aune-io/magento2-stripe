<?php

namespace Aune\Stripe\Gateway\Http\Client;

use Aune\Stripe\Gateway\Request\RetrieveDataBuilder;

class PaymentIntentRetrieve extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $paymentIntentId = $data[RetrieveDataBuilder::PAYMENT_INTENT];
        $paymentIntent = $this->adapter->paymentIntentRetrieve($paymentIntentId);

        // Assign payment method to customer if needed
        if (!isset($data[RetrieveDataBuilder::CUSTOMER])) {
            return $paymentIntent;
        }

        // Skip if already attached
        $paymentMethod = $this->adapter->paymentMethodRetrieve($paymentIntent->payment_method);
        if (!is_null($paymentMethod->customer)) {
            return $paymentIntent;
        }

        $paymentMethod->attach([
            'customer' => $data[RetrieveDataBuilder::CUSTOMER],
        ]);

        return $paymentIntent;
    }
}
