<?php

namespace Aune\Stripe\Gateway\Request;

use Stripe\Customer;

use Magento\Payment\Gateway\Request\BuilderInterface;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class PaymentDataBuilder implements BuilderInterface
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const CAPTURE = 'capture';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AmountProvider
     */
    private $amountProvider;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param Config $config
     * @param AmountProvider $amountProvider
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        AmountProvider $amountProvider,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->amountProvider = $amountProvider;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        
        // Prepare payload
        $currencyCode = $order->getCurrencyCode();
        $amount = $this->amountProvider->convert(
            $this->subjectReader->readAmount($buildSubject),
            $currencyCode
        );

        $data = [
            self::AMOUNT => $amount,
            self::CURRENCY => $currencyCode,
            self::CAPTURE => false,
        ];

        return $data;
    }
}
