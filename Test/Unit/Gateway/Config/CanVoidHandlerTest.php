<?php

namespace Aune\Stripe\Test\Unit\Gateway\Config;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Aune\Stripe\Gateway\Config\CanVoidHandler;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class CanVoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SubjectReader|PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;
    
    /**
     * @var PaymentDataObjectInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;
    
    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;
    
    /**
     * @var CanVoidHandler
     */
    private $canVoidHandler;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDOMock = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->canVoidHandler = new CanVoidHandler(
            $this->subjectReaderMock
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Config\CanVoidHandler::handle
     */
    public function testHandleWithoutPayment()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn(null);
        
        self::assertEquals(
            false,
            $this->canVoidHandler->handle($subject)
        );
    }
    
    /**
     * @covers \Aune\Stripe\Gateway\Config\CanVoidHandler::handle
     */
    public function testHandleWithAmountPaid()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects(self::once())
            ->method('getAmountPaid')
            ->willReturn(1);
        
        self::assertEquals(
            false,
            $this->canVoidHandler->handle($subject)
        );
    }
    
    /**
     * @covers \Aune\Stripe\Gateway\Config\CanVoidHandler::handle
     */
    public function testHandle()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects(self::once())
            ->method('getAmountPaid')
            ->willReturn(0);
        
        self::assertEquals(
            true,
            $this->canVoidHandler->handle($subject)
        );
    }
}
