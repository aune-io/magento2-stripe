<?php

namespace Aune\Stripe\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * @codeCoverageIgnore
 */
class FutureUsage implements ArrayInterface
{
    const USAGE_ON_SESSION = 'on_session';
    const USAGE_OFF_SESSION = 'off_session';

    /**
     * Possible actions on order place
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::USAGE_ON_SESSION,
                'label' => __('On Session'),
            ],
            [
                'value' => self::USAGE_OFF_SESSION,
                'label' => __('Off Session'),
            ]
        ];
    }
}
