<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder;
use Aune\Stripe\Observer\DataAssignObserver;

class ChargeCaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CURRENCY_CODE_DECIMAL = 'USD';
    const CURRENCY_CODE_ZERO_DECIMAL = 'JPY';
    
    /**
     * @var ChargeCaptureDataBuilder
     */
    private $builder;

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
     * @var OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockForAbstractClass(OrderAdapterInterface::class);

        $this->builder = new ChargeCaptureDataBuilder(
            new AmountProvider(),
            $this->subjectReaderMock
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder::build
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->builder->build($buildSubject);
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder::build
     */
    public function testBuildReadAmountException()
    {
        $chargeId = rand();
        $expectedResult = [
            ChargeCaptureDataBuilder::CHARGE_ID  => $chargeId,
            ChargeCaptureDataBuilder::AMOUNT  => null,
        ];
        
        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => null
        ];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($chargeId);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder::build
     */
    public function testBuildDecimal()
    {
        $chargeId = rand();
        $expectedResult = [
            ChargeCaptureDataBuilder::CHARGE_ID  => $chargeId,
            ChargeCaptureDataBuilder::AMOUNT  => 1000,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 10.00,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willReturn(10.00);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($chargeId);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder::build
     */
    public function testBuildZeroDecimal()
    {
        $chargeId = rand();
        
        $expectedResult = [
            ChargeCaptureDataBuilder::CHARGE_ID  => $chargeId,
            ChargeCaptureDataBuilder::AMOUNT  => 1000,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 1000.00,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willReturn(1000);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($chargeId);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_ZERO_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
