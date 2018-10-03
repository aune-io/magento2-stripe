<?php

namespace Aune\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Observer\DataAssignObserver;

class CustomerDataBuilder implements BuilderInterface
{
    const CUSTOMER = 'customer';
    const SOURCE = 'source';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    /**
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param TokenProvider $tokenProvider
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        TokenProvider $tokenProvider
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        
        // Handle customer token (extension version < 2.1.0)
        $details = json_decode($paymentToken->getTokenDetails(), true);
        if (empty($details['tokenType']) || $details['tokenType'] != TokenProvider::TOKEN_TYPE_SOURCE) {
            return [
                self::CUSTOMER => $paymentToken->getGatewayToken(),
            ];
        }
        
        // Fetch Stripe customer id and use vaulted token
        $stripeCustomerId = $this->tokenProvider->getCustomerStripeId(
            $paymentToken->getCustomerId()
        );
        
        return [
            self::CUSTOMER => $stripeCustomerId,
            self::SOURCE => $paymentToken->getGatewayToken(),
        ];
        
        
    }
}
