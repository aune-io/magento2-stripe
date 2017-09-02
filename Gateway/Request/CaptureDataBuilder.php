<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class CaptureDataBuilder implements BuilderInterface
{
    const CAPTURE = 'capture';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        return [
            self::CAPTURE => true,
        ];
    }
}
