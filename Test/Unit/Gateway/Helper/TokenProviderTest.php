<?php

namespace Aune\Stripe\Test\Unit\Gateway\Helper;

use Magento\Framework\Api\AttributeValue;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Model\Adapter\StripeAdapter;

class TokenProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomerRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepositoryMock;
    
    /**
     * @var CustomerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;
    
    /**
     * @var AttributeValue|PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeValueMock;
    
    /**
     * @var StripeAdapter|PHPUnit_Framework_MockObject_MockObject
     */
    private $stripeAdapterMock;
    
    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    protected function setUp()
    {
        $this->customerRepositoryMock = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->attributeValueMock = $this->getMockBuilder(AttributeValue::class)
            ->getMock();
        $this->stripeAdapterMock = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->tokenProvider = new TokenProvider(
            $this->customerRepositoryMock,
            $this->stripeAdapterMock
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Helper\TokenProvider::getCustomerStripeId
     */
    public function testGetCustomerStripeIdNotAssigned()
    {
        $customerId = rand();
        
        $this->customerRepositoryMock->expects(static::once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customerMock);
        
        self::assertEquals(
            null,
            $this->tokenProvider->getCustomerStripeId($customerId)
        );
    }
    
    /**
     * @covers \Aune\Stripe\Gateway\Helper\TokenProvider::getCustomerStripeId
     */
    public function testGetCustomerStripeIdAssigned()
    {
        $customerId = rand();
        $stripeId = rand();
        
        $this->customerRepositoryMock->expects(static::once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customerMock);
        
        $this->customerMock->expects(static::once())
            ->method('getCustomAttribute')
            ->with(TokenProvider::ATTRIBUTE_CODE)
            ->willReturn($this->attributeValueMock);
        
        $this->attributeValueMock->expects(static::once())
            ->method('getValue')
            ->willReturn($stripeId);
        
        self::assertEquals(
            $stripeId,
            $this->tokenProvider->getCustomerStripeId($customerId)
        );
    }
    
    /**
     * @covers \Aune\Stripe\Gateway\Helper\TokenProvider::setCustomerStripeId
     */
    public function testSetCustomerStripeId()
    {
        $customerId = rand();
        $stripeId = rand();
        
        $this->customerRepositoryMock->expects(static::once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customerMock);
        
        $this->customerMock->expects(static::once())
            ->method('setCustomAttribute')
            ->with(TokenProvider::ATTRIBUTE_CODE, $stripeId)
            ->willReturn($this->customerMock);
        
        $this->customerRepositoryMock->expects(static::once())
            ->method('save')
            ->with($this->customerMock)
            ->willReturn(true);
        
        $this->tokenProvider->setCustomerStripeId($customerId, $stripeId);
    }
}
