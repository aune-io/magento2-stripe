<?php

namespace Aune\Stripe\Gateway\Http\Client;

use Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder;

class ChargeCapture extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $chargeId = $data[ChargeCaptureDataBuilder::CHARGE_ID];
        unset($data[ChargeCaptureDataBuilder::CHARGE_ID]);
        
        return $this->adapter->chargeCapture(
            $chargeId,
            $data
        );
    }
}
