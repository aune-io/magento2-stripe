<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\CustomerDataBuilder;

class CustomerDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CUSTOMER = 'customerId';

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
        $this->paymentExtensionMock = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVaultPaymentToken'])
            ->getMock();
        $this->paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->builder = new CustomerDataBuilder(
            $this->configMock,
            $this->subjectReaderMock
        );
    }

    public function testBuild()
    {
        $expectedResult = [
            CustomerDataBuilder::CUSTOMER  => self::CUSTOMER,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];
        
        $this->paymentTokenMock->expects(static::once())
            ->method('getGatewayToken')
            ->willReturn(self::CUSTOMER);
        
        $this->paymentExtensionMock->expects(static::once())
            ->method('getVaultPaymentToken')
            ->willReturn($this->paymentTokenMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->paymentExtensionMock);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
