<?php

namespace Aune\Stripe\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Response\CardDetailsHandler;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class CardDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Aune\Stripe\Gateway\Response\CardDetailsHandler
     */
    private $cardHandler;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var \Aune\Stripe\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cardHandler = new CardDetailsHandler($this->config, $this->subjectReaderMock);
    }

    /**
     * @covers \Aune\Stripe\Gateway\Response\CardDetailsHandler::handle
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

        $this->payment->expects(static::once())
            ->method('setCcLast4');
        $this->payment->expects(static::once())
            ->method('setCcExpMonth');
        $this->payment->expects(static::once())
            ->method('setCcExpYear');
        $this->payment->expects(static::once())
            ->method('setCcType');
        $this->payment->expects(static::exactly(2))
            ->method('setAdditionalInformation');

        $this->cardHandler->handle($subject, $response);
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
            ->setMethods([
                'setCcLast4',
                'setCcExpMonth',
                'setCcExpYear',
                'setCcType',
                'setAdditionalInformation',
            ])
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
            'source' => [
                'card' => [
                    'brand' => 'Visa',
                    'exp_month' => 07,
                    'exp_year' => 29,
                    'last4' => 1234,
                ]
            ]
        ];
        
        return \Stripe\Util\Util::convertToStripeObject($attributes, []);
    }
}
