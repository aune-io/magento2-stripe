<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\AmountProvider;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\PaymentDataBuilder;
use Aune\Stripe\Observer\DataAssignObserver;

class PaymentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CURRENCY_CODE_DECIMAL = 'USD';
    const CURRENCY_CODE_ZERO_DECIMAL = 'JPY';

    /**
     * @var PaymentDataBuilder
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
     * @var OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

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
        $this->orderMock = $this->getMockForAbstractClass(OrderAdapterInterface::class);

        $this->builder = new PaymentDataBuilder(
            $this->configMock,
            new AmountProvider(),
            $this->subjectReaderMock
        );
    }

    /**
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
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadAmountException()
    {
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

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        $this->builder->build($buildSubject);
    }

    public function testBuildDecimal()
    {
        $expectedResult = [
            PaymentDataBuilder::AMOUNT  => 1000,
            PaymentDataBuilder::CURRENCY  => self::CURRENCY_CODE_DECIMAL,
            PaymentDataBuilder::CAPTURE => false,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 10.00,
        ];

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

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    public function testBuildZeroDecimal()
    {
        $expectedResult = [
            PaymentDataBuilder::AMOUNT  => 1000,
            PaymentDataBuilder::CURRENCY  => self::CURRENCY_CODE_ZERO_DECIMAL,
            PaymentDataBuilder::CAPTURE => false,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 1000.00,
        ];

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

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_ZERO_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
