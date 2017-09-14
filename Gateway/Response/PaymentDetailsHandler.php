<?php

namespace Aune\Stripe\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class PaymentDetailsHandler implements HandlerInterface
{
    const FAILURE_CODE = 'failure_code';
    const FAILURE_MESSAGE = 'failure_message';

    const OUTCOME = 'outcome';
    const OUTCOME_TYPE = 'type';
    const OUTCOME_NETWORK_STATUS = 'network_status';
    const OUTCOME_REASON = 'reason';
    const OUTCOME_SELLER_MESSAGE = 'seller_message';
    const OUTCOME_RISK_LEVEL = 'risk_level';
    
    /**
     * @var array
     */
    protected $additionalInformationMapping = [
        self::FAILURE_CODE,
        self::FAILURE_MESSAGE,
        self::OUTCOME => [
            self::OUTCOME_TYPE,
            self::OUTCOME_NETWORK_STATUS,
            self::OUTCOME_REASON,
            self::OUTCOME_SELLER_MESSAGE,
            self::OUTCOME_RISK_LEVEL,
        ],
    ];

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        
        /** @var \Stripe\Charge $charge */
        $charge = $this->subjectReader->readCharge($response);
        
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        foreach ($this->additionalInformationMapping as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    // Skip empty values
                    if (!isset($charge->$key->$item)) {
                        continue;
                    }
                    
                    // Copy over nested element
                    $payment->setAdditionalInformation(
                        $key . '_' . $item,
                        $charge->$key->$item
                    );
                }
            } elseif (isset($charge->$value)) {
                // Copy over element on base level
                $payment->setAdditionalInformation($value, $charge->$value);
            }
        }
    }
}
