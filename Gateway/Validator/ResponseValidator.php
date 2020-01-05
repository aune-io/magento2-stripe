<?php

namespace Aune\Stripe\Gateway\Validator;

use Stripe\PaymentIntent;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class ResponseValidator extends GeneralResponseValidator
{
    const STATUS_FAILED = 'failed';
    
    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    return [
                        $response instanceof PaymentIntent
                        && isset($response->status)
                        && $response->status != self::STATUS_FAILED,
                        [__('Wrong transaction status')]
                    ];
                }
            ]
        );
    }
}
