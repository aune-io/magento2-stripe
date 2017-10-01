<?php

namespace Aune\Stripe\Test\Unit\Model\Ui;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Model\Adapter\StripeAdapter;
use Aune\Stripe\Model\Ui\ConfigProvider;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    const SDK_URL = 'https://js.stripe.com/v3/';

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var StripeAdapter|PHPUnit_Framework_MockObject_MockObject
     */
    private $stripeAdapter;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stripeAdapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider(
            $this->config,
            $this->stripeAdapter
        );
    }

    /**
     * Run test getConfig method
     * 
     * @covers \Aune\Stripe\Model\Ui\ConfigProvider::getConfig
     * 
     * @dataProvider getConfigDataProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function testGetConfig($config, $expected)
    {
        foreach ($config as $method => $value) {
            $this->config->expects(static::once())
                ->method($method)
                ->willReturn($value);
        }

        static::assertEquals($expected, $this->configProvider->getConfig());
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        $isActive = true;
        $publishableKey = 'publishable-key';
        
        return [
            [
                'config' => [
                    'isActive' => $isActive,
                    'getPublishableKey' => $publishableKey,
                    'getSdkUrl' => self::SDK_URL,
                ],
                'expected' => [
                    'payment' => [
                        ConfigProvider::CODE => [
                            'isActive' => $isActive,
                            'publishableKey' => $publishableKey,
                            'sdkUrl' => self::SDK_URL,
                            'ccVaultCode' => ConfigProvider::VAULT_CODE
                        ],
                    ]
                ]
            ]
        ];
    }
}
