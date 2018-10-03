<?php

namespace Aune\Stripe\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class CardDetailsHandler implements HandlerInterface
{
    const CARD_BRAND = 'brand';
    const CARD_EXP_MONTH = 'exp_month';
    const CARD_EXP_YEAR = 'exp_year';
    const CARD_LAST4 = 'last4';
    const CARD_NUMBER = 'cc_number';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $charge = $this->subjectReader->readCharge($response);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $card = $charge->source->card;
        $payment->setCcLast4($card->last4);
        $payment->setCcExpMonth($card->exp_month);
        $payment->setCcExpYear($card->exp_year);
        $payment->setCcType($card->brand);

        // set card details to additional info
        $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $card->last4);
        $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $card->brand);
    }
}
