<?php

namespace Aune\Stripe\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Response\PaymentDetailsHandler;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class PaymentDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FAILURE_CODE = 'test-code';
    const OUTCOME_REASON = 'Test Reason';
    
    /**
     * @var \Aune\Stripe\Gateway\Response\PaymentDetailsHandler
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

        $this->handler = new PaymentDetailsHandler($this->subjectReaderMock);
    }

    /**
     * @covers \Aune\Stripe\Gateway\Response\PaymentDetailsHandler::handle
     */
    public function testHandle()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $charge = $this->getStripeCharge();

        $subject = ['payment' => $paymentData];
        $response = ['object' => $charge];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);
        $this->subjectReaderMock->expects(self::once())
            ->method('readCharge')
            ->with($response)
            ->willReturn($charge);

        $this->payment->expects(static::exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [PaymentDetailsHandler::FAILURE_CODE, self::FAILURE_CODE],
                [PaymentDetailsHandler::OUTCOME . '_' . PaymentDetailsHandler::OUTCOME_REASON, self::OUTCOME_REASON]
            );

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
     * Create Stripe Charge
     * 
     * @return \Stripe\Charge
     */
    private function getStripeCharge()
    {
        $attributes = [
            'object' => 'charge',
            PaymentDetailsHandler::FAILURE_CODE => self::FAILURE_CODE,
            PaymentDetailsHandler::OUTCOME => [
                PaymentDetailsHandler::OUTCOME_REASON => self::OUTCOME_REASON,
            ],
        ];
        
        return \Stripe\Util\Util::convertToStripeObject($attributes, []);
    }
}
