<?php

namespace Aune\Stripe\Gateway\Http\Client;

class ChargeRefund extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        return $this->adapter->refundCreate($data);
    }
}
