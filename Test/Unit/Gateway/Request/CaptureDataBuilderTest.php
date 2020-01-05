<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\CaptureDataBuilder;
use Aune\Stripe\Observer\DataAssignObserver;

class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
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

    /**
     * @var CaptureDataBuilder
     */
    private $builder;

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

        $this->builder = new CaptureDataBuilder(
            new AmountProvider(),
            $this->subjectReaderMock
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\CaptureDataBuilder::build
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
     * @covers \Aune\Stripe\Gateway\Request\CaptureDataBuilder::build
     */
    public function testBuildFullCapture()
    {
        $paymentIntent = rand();
        $expectedResult = [
            CaptureDataBuilder::PAYMENT_INTENT  => $paymentIntent,
            CaptureDataBuilder::AMOUNT_TO_CAPTURE  => null,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
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
            ->willReturn(null);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(DataAssignObserver::PAYMENT_INTENT)
            ->willReturn($paymentIntent);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn('EUR');

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\CaptureDataBuilder::build
     */
    public function testBuildPartialCapture()
    {
        $paymentIntent = rand();
        $expectedResult = [
            CaptureDataBuilder::PAYMENT_INTENT  => $paymentIntent,
            CaptureDataBuilder::AMOUNT_TO_CAPTURE  => 1000,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
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
            ->method('getAdditionalInformation')
            ->with(DataAssignObserver::PAYMENT_INTENT)
            ->willReturn($paymentIntent);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn('EUR');

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
