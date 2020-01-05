<?php

namespace Aune\Stripe\Gateway\Http\Client;

class RefundCreate extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        return $this->adapter->refundCreate($data);
    }
}
