<?php

namespace Aune\Stripe\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_PUBLISHABLE_KEY = 'publishable_key';
    const KEY_SECRET_KEY = 'secret_key';
    const KEY_STORE_CUSTOMER = 'store_customer';
    const KEY_SDK_URL = 'sdk_url';
    const KEY_CC_TYPES_STRIPE_MAPPER = 'cctypes_stripe_mapper';

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Encryptor $encryptor
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        
        $this->encryptor = $encryptor;
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
     * Return wether the customer should be stored in Stripe or not
     * 
     * @return bool
     */
    public function isStoreCustomerEnabled()
    {
        return (bool) $this->getValue(self::KEY_STORE_CUSTOMER);
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
