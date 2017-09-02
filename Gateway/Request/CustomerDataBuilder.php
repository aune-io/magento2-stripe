<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Observer\DataAssignObserver;

class CustomerDataBuilder implements BuilderInterface
{
    const CUSTOMER = 'customer';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
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
        $payment = $paymentDO->getPayment();
        
        // Check if a vaulted payment method is being used
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        
        return [
            self::CUSTOMER => $paymentToken->getGatewayToken(),
        ];
    }
}
