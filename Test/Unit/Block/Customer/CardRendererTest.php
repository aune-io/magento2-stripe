<?php

namespace Aune\Stripe\Test\Unit\Block\Customer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Model\CcConfigProvider;

use Aune\Stripe\Block\Customer\CardRenderer;
use Aune\Stripe\Model\Ui\ConfigProvider;

class CardRendererTest extends \PHPUnit\Framework\TestCase
{
    const TOKEN_MASKED_CC = '1111';
    const TOKEN_EXPIRATION_DATE = '10/26';
    const TOKEN_TYPE = 'VI';
    
    const ICON_URL = 'icon-url';
    const ICON_WIDTH = '140';
    const ICON_HEIGHT = '90';
    
    /**
     * @var PaymentTokenInterface|MockObject
     */
    private $paymentToken;
    
    /**
     * @var CcConfigProvider|MockObject
     */
    private $iconsProvider;

    /**
     * @var CardRenderer
     */
    private $block;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->paymentToken = $this->getMockForAbstractClass(PaymentTokenInterface::class);

        $this->iconsProvider = $this->getMockBuilder(CcConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = $this->objectManager->getObject(CardRenderer::class, [
            'iconsProvider' => $this->iconsProvider,
        ]);
    }

    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::canRender
     */
    public function testCanRenderTrue()
    {
        $this->paymentToken->expects(static::once())
            ->method('getPaymentMethodCode')
            ->willReturn(ConfigProvider::CODE);

        static::assertEquals(true, $this->block->canRender($this->paymentToken));
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::canRender
     */
    public function testCanRenderFalse()
    {
        $this->paymentToken->expects(static::once())
            ->method('getPaymentMethodCode')
            ->willReturn(rand());

        static::assertEquals(false, $this->block->canRender($this->paymentToken));
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::getNumberLast4Digits
     */
    public function testGetNumberLast4Digits()
    {
        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn($this->getTokenDetails());

        $this->block->render($this->paymentToken);

        static::assertEquals(self::TOKEN_MASKED_CC, $this->block->getNumberLast4Digits());
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::getExpDate
     */
    public function testGetExpDate()
    {
        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn($this->getTokenDetails());

        $this->block->render($this->paymentToken);

        static::assertEquals(self::TOKEN_EXPIRATION_DATE, $this->block->getExpDate());
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::getIconUrl
     */
    public function testGetIconUrl()
    {
        $this->iconsProvider->expects(static::atLeastOnce())
            ->method('getIcons')
            ->willReturn($this->getIcons());
        
        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn($this->getTokenDetails());

        $this->block->render($this->paymentToken);

        static::assertEquals(self::ICON_URL, $this->block->getIconUrl());
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::getIconWidth
     */
    public function testGetIconWidth()
    {
        $this->iconsProvider->expects(static::atLeastOnce())
            ->method('getIcons')
            ->willReturn($this->getIcons());
        
        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn($this->getTokenDetails());

        $this->block->render($this->paymentToken);

        static::assertEquals(self::ICON_WIDTH, $this->block->getIconWidth());
    }
    
    /**
     * @covers \Aune\Stripe\Block\Customer\CardRenderer::getIconHeight
     */
    public function testGetIconHeight()
    {
        $this->iconsProvider->expects(static::atLeastOnce())
            ->method('getIcons')
            ->willReturn($this->getIcons());
        
        $this->paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn($this->getTokenDetails());

        $this->block->render($this->paymentToken);

        static::assertEquals(self::ICON_HEIGHT, $this->block->getIconHeight());
    }
    
    /**
     * Return json encoded mock token details
     */
    protected function getTokenDetails()
    {
        return json_encode([
            'maskedCC' => self::TOKEN_MASKED_CC,
            'expirationDate' => self::TOKEN_EXPIRATION_DATE,
            'type' => self::TOKEN_TYPE,
        ]);
    }
    
    /**
     * Return json encoded mock token icon
     */
    protected function getIcons()
    {
        return [
            self::TOKEN_TYPE => [
                'url' => self::ICON_URL,
                'width' => self::ICON_WIDTH,
                'height' => self::ICON_HEIGHT,
            ]
        ];
    }
}
