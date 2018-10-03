<?php

namespace Aune\Stripe\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;

use Aune\Stripe\Gateway\Config\Config;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    const METHOD_CODE = 'aune_stripe';
    
    /**
     * @var ScopeConfigInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;
    
    /**
     * @var Encryptor|PHPUnit_Framework_MockObject_MockObject
     */
    private $encryptorMock;
    
    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->encryptorMock = $this->getMockBuilder(Encryptor::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->config = new Config(
            $this->scopeConfigMock,
            $this->encryptorMock,
            self::METHOD_CODE
        );
    }

    /**
     * @dataProvider getConfigDataProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function testGetConfigValue($key, $method , $in, $out, $secret)
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('payment/' . self::METHOD_CODE . '/' . $key)
            ->willReturn($in);
        
        if ($secret) {
            $this->encryptorMock->expects(self::once())
                ->method('decrypt')
                ->with($in)
                ->willReturn($in);
        }
        
        self::assertEquals(
            $out,
            $this->config->$method()
        );
    }
    
    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            ['key' => Config::KEY_ACTIVE, 'method' => 'isActive', 'in' => '1', 'out' => true, 'secret' => false],
            ['key' => Config::KEY_ACTIVE, 'method' => 'isActive', 'in' => '0', 'out' => false, 'secret' => false],
            ['key' => Config::KEY_PUBLISHABLE_KEY, 'method' => 'getPublishableKey', 'in' => 'test', 'out' => 'test', 'secret' => false],
            ['key' => Config::KEY_SECRET_KEY, 'method' => 'getSecretKey', 'in' => 'test', 'out' => 'test', 'secret' => true],
            ['key' => Config::KEY_SDK_URL, 'method' => 'getSdkUrl', 'in' => 'test', 'out' => 'test', 'secret' => false],
            ['key' => Config::KEY_STORE_CUSTOMER, 'method' => 'isStoreCustomerEnabled', 'in' => '1', 'out' => true, 'secret' => false],
            ['key' => Config::KEY_STORE_CUSTOMER, 'method' => 'isStoreCustomerEnabled', 'in' => '0', 'out' => false, 'secret' => false],
            ['key' => Config::KEY_CC_TYPES_STRIPE_MAPPER, 'method' => 'getCcTypesMapper', 'in' => null, 'out' => [], 'secret' => false],
            ['key' => Config::KEY_CC_TYPES_STRIPE_MAPPER, 'method' => 'getCcTypesMapper', 'in' => '{"american-express":"AE","discover":"DI"}', 'out' => ['american-express' => 'AE', 'discover' => 'DI'], 'secret' => false],
        ];
    }
}
