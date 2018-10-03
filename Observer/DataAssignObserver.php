<?php

namespace Aune\Stripe\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const SOURCE = 'source';

    /**
     * @var array
     */
    protected $additionalInfo = [
        self::SOURCE,
    ];

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInfo as $additionalInfoKey) {
            if (isset($additionalData[$additionalInfoKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInfoKey,
                    $additionalData[$additionalInfoKey]
                );
            }
        }
    }
}
