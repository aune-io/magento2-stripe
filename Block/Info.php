<?php

namespace Aune\Stripe\Block;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * @codeCoverageIgnore
 */
class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return \Magento\Framework\Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }
}
