<?php

namespace Aune\Stripe\Gateway\Helper;

class AmountProvider
{
    /**
     * Zero decimal currencies
     * 
     * Reference:
     * "Supported zero-decimal currencies are shown as links in the supported charge currencies"
     * https://stripe.com/docs/currencies#charge-currencies
     * 
     * @var array
     */
    private $zeroDecimal = [
        'BIF',
        'XAF',
        'XPF',
        'CLP',
        'KMF',
        'DJF',
        'GNF',
        'JPY',
        'MGA',
        'PYG',
        'RWF',
        'KRW',
        'VUV',
        'VND',
        'XOF',
    ];
    
    /**
     * Return converted amount based on currency
     * 
     * @param $amount float The amount to convert
     * @param @currency string The currency code
     * 
     * @return int
     */
    public function convert($amount, $currency)
    {
        $multiplier = in_array($currency, $this->zeroDecimal) ? 1 : 100;

        return (int)($multiplier * $amount);
    }
}
