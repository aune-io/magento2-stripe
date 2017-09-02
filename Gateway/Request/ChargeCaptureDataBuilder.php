<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class ChargeCaptureDataBuilder implements BuilderInterface
{
    const CHARGE_ID = 'chargeId';
    const AMOUNT = 'amount';

    /**
     * @var AmountProvider
     */
    private $amountProvider;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param AmountProvider $amountProvider
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        AmountProvider $amountProvider,
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountProvider = $amountProvider;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $amount = null;
        
        try {
            $currencyCode = $order->getCurrencyCode();
            $amount = $this->amountProvider->convert(
                $this->subjectReader->readAmount($buildSubject),
                $currencyCode
            );
        } catch (\InvalidArgumentException $e) { }
        
        return [
            self::CHARGE_ID => $payment->getLastTransId(),
            self::AMOUNT => $amount,
        ];
    }
}
