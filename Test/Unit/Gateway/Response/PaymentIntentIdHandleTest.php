<?php

namespace Aune\Stripe\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Response\PaymentIntentIdHandler;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class PaymentIntentIdHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handlingSubject = [
            'payment' => $paymentDO
        ];

        $paymentIntent = \Stripe\Util\Util::convertToStripeObject([
            'object' => 'payment_intent',
            'id' => 1,
        ], []);
        
        $response = [
            'object' => $paymentIntent,
        ];

        $subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subjectReader->expects(static::once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDO);
        $paymentDO->expects(static::atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $subjectReader->expects(static::once())
            ->method('readPaymentIntent')
            ->with($response)
            ->willReturn($paymentIntent);

        $paymentInfo->expects(static::once())
            ->method('setTransactionId')
            ->with(1);

        $paymentInfo->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false);
        $paymentInfo->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $handler = new PaymentIntentIdHandler($subjectReader);
        $handler->handle($handlingSubject, $response);
    }
}
