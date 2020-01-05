<?php

namespace Aune\Stripe\Gateway\Request;

use Stripe\Customer;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface as PaymentInfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Model\Adapter\StripeAdapter;
use Aune\Stripe\Observer\DataAssignObserver;

class RetrieveDataBuilder implements BuilderInterface
{
    const CUSTOMER = 'customer';
    const PAYMENT_INTENT = 'payment_intent';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var StripeAdapter
     */
    private $stripeAdapter;

    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    /**
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param StripeAdapter $stripeAdapter
     * @param TokenProvider $tokenProvider
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        StripeAdapter $stripeAdapter,
        TokenProvider $tokenProvider
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->stripeAdapter = $stripeAdapter;
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $orderAdapter = $paymentDO->getOrder();

        $paymentIntentId = $payment->getAdditionalInformation(DataAssignObserver::PAYMENT_INTENT);
        $data = [
            self::PAYMENT_INTENT => $paymentIntentId,
        ];

        // If vaulting is enabled, assign the payment intent to the customer
        if ($this->canVaultCustomer($orderAdapter, $payment)) {

            // Attach new source to customer
            $stripeCustomer = $this->getStripeCustomer($orderAdapter);

            $data[self::CUSTOMER] = $stripeCustomer->id;
        }

        return $data;
    }
    
    /**
     * Check if the customer can be vaulted for the given order
     */
    private function canVaultCustomer(
        OrderAdapterInterface $order,
        PaymentInfoInterface $payment
    ) {
        if (is_null($order->getCustomerId())) {
            return false;
        }
        
        return $this->config->isStoreCustomerEnabled() ||
            $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);
    }
    
    /**
     * Get Stripe customer if it exists, otherwise create a new one and assign
     * it to the Magento customer
     */
    private function getStripeCustomer(OrderAdapterInterface $orderAdapter)
    {
        // Check if the customer already has a stripe id
        $customerId = $orderAdapter->getCustomerId();
        
        $stripeId = $this->tokenProvider->getCustomerStripeId($customerId);
        if ($stripeId) {
            return $this->stripeAdapter->customerRetrieve($stripeId);
        }
        
        $addressAdapter = $orderAdapter->getBillingAddress();
        $stripeCustomer = $this->stripeAdapter->customerCreate([
            'email' => $addressAdapter->getEmail(),
            'description' => $addressAdapter->getFirstname() . ' ' . $addressAdapter->getLastname(),
        ]);
        
        // Assign the customer Stripe id to the Magento customer
        $this->tokenProvider->setCustomerStripeId(
            $customerId,
            $stripeCustomer->id
        );
        
        return $stripeCustomer;
    }
}