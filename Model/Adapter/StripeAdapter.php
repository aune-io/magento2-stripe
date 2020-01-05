<?php

namespace Aune\Stripe\Model\Adapter;

use Stripe\Charge;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\Stripe;

use Magento\Framework\Module\ModuleListInterface;
use Aune\Stripe\Gateway\Config\Config;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class StripeAdapter
{
    const MODULE_NAME = 'Aune_Stripe';
    const APPLICATION_NAME = 'AuneStripeM2';
    const APPLICATION_URL = 'https://gitbub.com/aune/magento2-stripe';
    const API_VERSION = '2019-12-03';
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param Config $config
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Config $config,
        ModuleListInterface $moduleList
    ) {
        $this->config = $config;
        $this->moduleList = $moduleList;
        
        $this->initCredentials();
    }

    /**
     * Initializes credentials.
     * 
     * @return void
     */
    protected function initCredentials()
    {
        // Set application version
        $module = $this->moduleList->getOne(self::MODULE_NAME);
        $this->setAppInfo(
            self::APPLICATION_NAME,
            $module['setup_version'],
            self::APPLICATION_URL
        );
        
        // Set secret key
        $this->setApiKey($this->config->getSecretKey());
        
        // Pinpoint API version
        $this->setApiVersion(self::API_VERSION);
    }
    
    /**
     * @param string|null $value
     * @return mixed
     */
    public function setApiKey($value = null)
    {
        return Stripe::setApiKey($value);
    }
    
    /**
     * @param string $applicationName
     * @param string $applicationVersion
     * @param string $applicationUrl
     * @return mixed
     */
    public function setAppInfo($applicationName, $applicationVersion, $applicationUrl)
    {
        return Stripe::setAppInfo($applicationName, $applicationVersion, $applicationUrl);
    }
    
    /**
     * @param string|null $value
     * @return mixed
     */
    public function setApiVersion($value = null)
    {
        return Stripe::setApiVErsion($value);
    }

    /**
     * @param array $attributes
     * @return \Stripe\Customer|\Stripe\Error\Base
     */
    public function customerCreate(array $attributes)
    {
        return Customer::create($attributes);
    }
    
    /**
     * @param string $customerId
     * @return \Stripe\Customer|\Stripe\Error\Base
     */
    public function customerRetrieve(string $customerId)
    {
        return Customer::retrieve($customerId);
    }
    
    /**
     * @param \Stripe\Customer $customer
     * @param string $sourceId
     * @return \Stripe\Source|\Stripe\Error\Base
     */
    public function customerAttachSource(
        \Stripe\Customer $customer,
        string $sourceId
    ) {
        return $customer->sources->create([
            'source' => $sourceId,
        ]);
    }

    /**
     * @param array $params
     * @return \Stripe\PaymentIntent|\Stripe\Error\Base
     */
    public function paymentIntentCreate($params)
    {
        return PaymentIntent::create($params);
    }

    /**
     * @param string $paymentIntentId
     * @return \Stripe\PaymentIntent|\Stripe\Error\Base
     */
    public function paymentIntentRetrieve($paymentIntentId)
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * @param string $paymentMethodId
     * @return \Stripe\PaymentMethod|\Stripe\Error\Base
     */
    public function paymentMethodRetrieve($paymentMethodId)
    {
        return PaymentMethod::retrieve($paymentMethodId);
    }

    /**
     * @param array $params
     * @return \Stripe\Refund|\Stripe\Error\Base
     */
    public function refundCreate($params)
    {
        return Refund::create($params);
    }
}
