<?php

namespace Aune\Stripe\Test\Unit\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

use Aune\Stripe\Model\Ui\ConfigProvider;
use Aune\Stripe\Model\Ui\TokenUiComponentProvider;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

class TokenUiComponentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTokenInterface|MockObject
     */
    private $paymentToken;

    /**
     * @var TokenUiComponentInterface|MockObject
     */
    private $tokenComponent;

    /**
     * @var TokenUiComponentInterfaceFactory|MockObject
     */
    private $componentFactory;

    /**
     * @var TokenUiComponentProvider
     */
    private $componentProvider;

    protected function setUp()
    {
        $this->componentFactory = $this->getMockBuilder(TokenUiComponentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->tokenComponent = $this->getMockForAbstractClass(TokenUiComponentInterface::class);

        $this->paymentToken = $this->getMockForAbstractClass(PaymentTokenInterface::class);

        $this->componentProvider = new TokenUiComponentProvider(
            $this->componentFactory
        );
    }

    /**
     * @covers \Aune\Stripe\Model\Ui\TokenUiComponentProvider::getComponentForToken
     */
    public function testGetComponentForToken()
    {
        $tokenDetails = [
            'maskedCC' => '1111',
            'expirationDate' => '10/26',
            'type' => 'VI',
        ];
        $hash = rand();
        
        $tokenCreateParameters = [
            'config' => [
                'code' => ConfigProvider::VAULT_CODE,
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => $tokenDetails,
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $hash
            ],
            'name' => 'Aune_Stripe/js/view/payment/method-renderer/vault'
        ];

        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn(json_encode($tokenDetails));

        $this->componentFactory->expects(static::once())
            ->method('create')
            ->with($tokenCreateParameters)
            ->willReturn($this->tokenComponent);
        
        $this->paymentToken->expects(static::once())
            ->method('getPublicHash')
            ->willReturn($hash);
        
        $actual = $this->componentProvider->getComponentForToken($this->paymentToken);

        static::assertEquals($this->tokenComponent, $actual);
    }
}
