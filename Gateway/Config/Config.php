<?php

namespace Aune\Stripe\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_PUBLISHABLE_KEY = 'publishable_key';
    const KEY_SECRET_KEY = 'secret_key';
    const KEY_STORE_CUSTOMER = 'store_customer';
    const KEY_STORE_FUTURE_USAGE = 'store_future_usage';
    const KEY_SDK_URL = 'sdk_url';
    const KEY_CC_TYPES_STRIPE_MAPPER = 'cctypes_stripe_mapper';

    const PAYMENT_INTENT_PATH = 'aune_stripe/paymentintent/generate';

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Encryptor $encryptor
     * @param UrlInterface $urlHelper
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        UrlInterface $urlHelper,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->encryptor = $encryptor;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Get Payment configuration status
     * 
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }
    
    /**
     * Get payment action
     * 
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->getValue(self::KEY_PAYMENT_ACTION);
    }
    
    /**
     * Get publishable key
     * 
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->getValue(self::KEY_PUBLISHABLE_KEY);
    }
    
    /**
     * Get secret key
     * 
     * @return string
     */
    public function getSecretKey()
    {
        $value = $this->getValue(self::KEY_SECRET_KEY);
        return $value ? $this->encryptor->decrypt($value) : $value;
    }
    
    /**
     * Get sdk url
     * 
     * @return string
     */
    public function getSdkUrl()
    {
        return $this->getValue(self::KEY_SDK_URL);
    }
    
    /**
     * Get payment intent generation url
     * 
     * @return string
     */
    public function getPaymentIntentUrl()
    {
        return $this->urlHelper->getUrl(self::PAYMENT_INTENT_PATH);
    }
    
    /**
     * Return wether the customer should be stored in Stripe or not
     * 
     * @return bool
     */
    public function isStoreCustomerEnabled()
    {
        return (bool) $this->getValue(self::KEY_STORE_CUSTOMER);
    }
    
    /**
     * Return the configured future usage setting
     * 
     * @return string
     */
    public function getStoreFutureUsage()
    {
        return $this->getValue(self::KEY_STORE_FUTURE_USAGE);
    }
    
    /**
     * Retrieve mapper between Magento and Stripe card types
     *
     * @return array
     */
    public function getCcTypesMapper()
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_STRIPE_MAPPER),
            true
        );

        return is_array($result) ? $result : [];
    }
}
