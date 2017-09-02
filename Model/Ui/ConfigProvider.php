<?php

namespace Aune\Stripe\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Aune\Stripe\Gateway\Config\Config;

final class ConfigProvider implements ConfigProviderInterface
{
    const CODE       = 'aune_stripe';
    const VAULT_CODE = 'aune_stripe_vault';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'publishableKey' => $this->config->getPublishableKey(),
                    'sdkUrl' => $this->config->getSdkUrl(),
                    'ccVaultCode' => self::VAULT_CODE
                ],
            ]
        ];
    }
}
