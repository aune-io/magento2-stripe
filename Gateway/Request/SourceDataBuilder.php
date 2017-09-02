<?php

namespace Aune\Stripe\Gateway\Request;

use Stripe\Customer;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Model\Adapter\StripeAdapter;
use Aune\Stripe\Observer\DataAssignObserver;

class SourceDataBuilder implements BuilderInterface
{
    const SOURCE = 'source';
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
     * @param StripeAdapter $stripeAdapter
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        StripeAdapter $stripeAdapter
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->stripeAdapter = $stripeAdapter;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        
        $sourceId = $payment->getAdditionalInformation(DataAssignObserver::SOURCE);
        
        // Store the customer if module is configured to do so or if customer is vaulting the card
        $shouldStore = $this->config->getStoreCustomer() ||
            $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);
        
        if ($shouldStore) {
            $customerId = $this->getStripeCustomerId(
                $paymentDO->getOrder(),
                $sourceId
            );
            return [
                self::CUSTOMER => $customerId,
            ];
        } else {
            return [
                self::SOURCE => $sourceId,
            ];
        }
    }
    
    /**
     * Create Stripe customer and return its id
     */
    protected function getStripeCustomerId($orderAdapter, $sourceId)
    {
        $addressAdapter = $orderAdapter->getBillingAddress();
        $stripeCustomer = $this->stripeAdapter->customerCreate([
            'email' => $addressAdapter->getEmail(),
            'description' => $addressAdapter->getFirstname() . ' ' . $addressAdapter->getLastname(),
            'source' => $sourceId,
        ]);
        
        return $stripeCustomer->id;
    }
}
