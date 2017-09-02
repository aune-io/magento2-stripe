<?php

namespace Aune\Stripe\Gateway\Http\Client;

class Charge extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        return $this->adapter->chargeCreate($data);
    }
}
