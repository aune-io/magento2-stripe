<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Gateway\Request\CustomerDataBuilder;

class CustomerDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CUSTOMER_ID = 'cus_123';
    const SOURCE_ID = 'src_123';

    /**
     * @var CustomerDataBuilder
     */
    private $builder;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;
    
    /**
     * @var TokenProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenProviderMock;
    
    /**
     * @var OrderPaymentExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentExtensionMock;
    
    /**
     * @var PaymentTokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenMock;
    
    protected function setUp()
    {
        $this->paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenProviderMock = $this->getMockBuilder(TokenProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentExtensionMock = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVaultPaymentToken'])
            ->getMock();
        $this->paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->builder = new CustomerDataBuilder(
            $this->configMock,
            $this->subjectReaderMock,
            $this->tokenProviderMock
        );
    }

    /**
     * Tests customer data builder with source token
     * 
     * @covers \Aune\Stripe\Gateway\Request\CustomerDataBuilder::build
     */
    public function testBuildWithSourceToken()
    {
        $expectedResult = [
            CustomerDataBuilder::CUSTOMER => self::CUSTOMER_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->paymentExtensionMock);
        
        $this->paymentExtensionMock->expects(static::once())
            ->method('getVaultPaymentToken')
            ->willReturn($this->paymentTokenMock);
        
        $this->paymentTokenMock->expects(static::once())
            ->method('getGatewayToken')
            ->willReturn(self::CUSTOMER_ID);
            
        $this->paymentTokenMock->expects(self::once())
            ->method('getTokenDetails')
            ->willReturn('{"type":"VI","maskedCC":1234,"expirationDate":"07\/2029"}');

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Tests customer data builder with customer token
     * 
     * @covers \Aune\Stripe\Gateway\Request\CustomerDataBuilder::build
     */
    public function testBuildWithCustomerToken()
    {
        $expectedResult = [
            CustomerDataBuilder::CUSTOMER => self::CUSTOMER_ID,
            CustomerDataBuilder::SOURCE => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];
        
        $magentoCustomerId = rand();

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->paymentExtensionMock);
        
        $this->paymentExtensionMock->expects(static::once())
            ->method('getVaultPaymentToken')
            ->willReturn($this->paymentTokenMock);
        
        $this->paymentTokenMock->expects(static::once())
            ->method('getGatewayToken')
            ->willReturn(self::SOURCE_ID);
        
        $this->paymentTokenMock->expects(self::once())
            ->method('getTokenDetails')
            ->willReturn('{"tokenType":"source","type":"VI","maskedCC":1234,"expirationDate":"07\/2029"}');
        
        $this->paymentTokenMock->expects(static::once())
            ->method('getCustomerId')
            ->willReturn($magentoCustomerId);
        
        $this->tokenProviderMock->expects(static::once())
            ->method('getCustomerStripeId')
            ->with($magentoCustomerId)
            ->willReturn(self::CUSTOMER_ID);
        
        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
