<?php

namespace Aune\Stripe\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;

use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Response\RefundHandler;

class RefundHandlerTest extends \PHPUnit\Framework\TestCase
{
    const REFUND_ID = 'ref_123';
    
    /**
     * @var \Aune\Stripe\Gateway\Response\RefundHandler
     */
    private $handler;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new RefundHandler($this->subjectReaderMock);
    }

    /**
     * @covers \Aune\Stripe\Gateway\Response\RefundHandler::handle
     */
    public function testHandleCloseParent()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $refund = $this->getStripeRefund();

        $subject = ['payment' => $paymentData];
        $response = ['object' => $refund];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readRefund')
            ->with($response)
            ->willReturn($refund);

        $this->payment->expects(self::once())
            ->method('setTransactionId')
            ->with(self::REFUND_ID);
        
        $this->payment->expects(self::once())
            ->method('setIsTransactionClosed')
            ->with(true);
        
        $this->payment->expects(self::once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        $this->handler->handle($subject, $response);
    }
    
    /**
     * @covers \Aune\Stripe\Gateway\Response\RefundHandler::handle
     */
    public function testHandle()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $refund = $this->getStripeRefund();

        $subject = ['payment' => $paymentData];
        $response = ['object' => $refund];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readRefund')
            ->with($response)
            ->willReturn($refund);

        $invoice = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $creditmemo->expects(self::once())
            ->method('getInvoice')
            ->willReturn($invoice);

        $this->payment->expects(self::once())
            ->method('getCreditmemo')
            ->willReturn($creditmemo);

        $invoice->expects(self::once())
            ->method('canRefund')
            ->willReturn(true);

        $this->payment->expects(self::once())
            ->method('setTransactionId')
            ->with(self::REFUND_ID);
        
        $this->payment->expects(self::once())
            ->method('setIsTransactionClosed')
            ->with(true);
        
        $this->payment->expects(self::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $this->handler->handle($subject, $response);
    }

    /**
     * Create mock for payment data object and order payment
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentDataObjectMock()
    {
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        return $mock;
    }

    /**
     * Create Stripe Refund
     * 
     * @return \Stripe\Refund
     */
    private function getStripeRefund()
    {
        $attributes = [
            'object' => 'refund',
            'id' => self::REFUND_ID,
        ];
        
        return \Stripe\Util\Util::convertToStripeObject($attributes, []);
    }
}
