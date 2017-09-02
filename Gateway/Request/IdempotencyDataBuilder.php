<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class IdempotencyDataBuilder implements BuilderInterface
{
    const IDEMPOTENCY_KEY = 'idempotency_key';

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
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        
        $idempotencyKey = $order->getOrderIncrementId();
        if ($prefix = $this->config->getIdempotencyKeyPrefix()) {
            $idempotencyKey = $prefix . $idempotencyKey;
        }

        return [
            self::IDEMPOTENCY_KEY => $idempotencyKey,
        ];
    }
}
