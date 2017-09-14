<?php

namespace Aune\Stripe\Model\Adapter;

use Stripe\Charge;
use Stripe\Customer;
use Stripe\Refund;
use Stripe\Stripe;

use Magento\Framework\Module\ModuleListInterface;
use Aune\Stripe\Gateway\Config\Config;

class StripeAdapter
{
    const MODULE_NAME = 'Aune_Stripe';
    const APPLICATION_NAME = 'AuneStripeM2';
    const APPLICATION_URL = 'https://gitbub.com/aune/magento2-stripe';
    const API_VERSION = '2017-08-15';
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * 
     * @param string|null $value
     * @return mixed
     */
    public function setApiKey($value = null)
    {
        return Stripe::setApiKey($value);
    }
    
    /**
     * @codeCoverageIgnore
     * 
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
     * @codeCoverageIgnore
     * 
     * @param string|null $value
     * @return mixed
     */
    public function setApiVersion($value = null)
    {
        return Stripe::setApiVErsion($value);
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param array $attributes
     * @return \Stripe\Customer|\Stripe\Error\Base
     */
    public function customerCreate(array $attributes)
    {
        return Customer::create($attributes);
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param string $chargeId
     * @return \Stripe\Charge|null
     */
    public function chargeRetrieve($chargeId)
    {
        try {
            return Charge::retrieve($chargeId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param array $attributes
     * @return \Stripe\Charge|\Stripe\Error\Base
     */
    public function chargeCreate(array $attributes)
    {
        return Charge::create($attributes);
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param string $chargeId
     * @param null|array $params
     * @return \Stripe\Charge|\Stripe\Error\Base
     */
    public function chargeCapture($chargeId, $params = null)
    {
        $charge = $this->chargeRetrieve($chargeId);
        if (!($charge instanceof Charge)) {
            return $charge;
        }

        return $charge->capture($params);
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param array $params
     * @return \Stripe\Refund|\Stripe\Error\Base
     */
    public function refundCreate($params)
    {
        return Refund::create($params);
    }
}
