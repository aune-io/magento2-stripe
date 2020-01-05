<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Observer\DataAssignObserver;

class CaptureDataBuilder implements BuilderInterface
{
    const PAYMENT_INTENT = 'payment_intent';
    const AMOUNT_TO_CAPTURE = 'amount_to_capture';

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

        try {

            $currencyCode = $order->getCurrencyCode();
            $amount = $this->amountProvider->convert(
                $this->subjectReader->readAmount($buildSubject),
                $currencyCode
            );

        } catch (\InvalidArgumentException $e) {
            $amount = null;
        }

        return [
            self::PAYMENT_INTENT => $payment->getAdditionalInformation(DataAssignObserver::PAYMENT_INTENT),
            self::AMOUNT_TO_CAPTURE => $amount,
        ];
    }
}
