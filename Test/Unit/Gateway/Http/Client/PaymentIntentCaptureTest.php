<?php

namespace Aune\Stripe\Test\Unit\Gateway\Http\Client;

use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Aune\Stripe\Gateway\Http\Client\PaymentIntentCapture;
use Aune\Stripe\Gateway\Request\CaptureDataBuilder;
use Aune\Stripe\Model\Adapter\StripeAdapter;

class PaymentIntentCaptureTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_INTENT_ID = 'pi_123';

    /**
     * @var PaymentIntentCapture
     */
    private $model;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var StripeAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $adapter;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $criticalLoggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new PaymentIntentCapture($criticalLoggerMock, $this->loggerMock, $this->adapter);
    }

    /**
     * Run test placeRequest method (exception)
     *
     * @return void
     *
     * @expectedException \Magento\Payment\Gateway\Http\ClientException
     * @expectedExceptionMessage Test message
     */
    public function testPlaceRequestException()
    {
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => $this->getTransferData(),
                    'client' => PaymentIntentCapture::class,
                    'response' => []
                ]
            );

        $this->adapter->expects($this->once())
            ->method('paymentIntentRetrieve')
            ->willThrowException(new \Exception('Test message'));

        /** @var TransferInterface|\PHPUnit_Framework_MockObject_MockObject $transferObjectMock */
        $transferObjectMock = $this->getTransferObjectMock();

        $this->model->placeRequest($transferObjectMock);
    }

    /**
     * Run test placeRequest method
     *
     * @return void
     */
    public function testPlaceRequestSuccess()
    {
        $paymentIntentMock = $this->getMockBuilder(\Stripe\PaymentIntent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter->expects(self::once())
            ->method('paymentIntentRetrieve')
            ->with(self::PAYMENT_INTENT_ID)
            ->willReturn($paymentIntentMock);

        $paymentIntentMock->expects(self::once())
            ->method('capture');

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => $this->getTransferData(),
                    'client' => PaymentIntentCapture::class,
                    'response' => (array)$paymentIntentMock,
                ]
            );

        $actualResult = $this->model->placeRequest($this->getTransferObjectMock());

        $this->assertTrue(is_object($actualResult['object']));
        $this->assertEquals(['object' => $paymentIntentMock], $actualResult);
    }

    /**
     * @return TransferInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTransferObjectMock()
    {
        $transferObjectMock = $this->getMockForAbstractClass(TransferInterface::class);
        $transferObjectMock->expects($this->once())
            ->method('getBody')
            ->willReturn($this->getTransferData());

        return $transferObjectMock;
    }

    /**
     * @return array
     */
    private function getTransferData()
    {
        return [
            CaptureDataBuilder::PAYMENT_INTENT => self::PAYMENT_INTENT_ID,
            'test-data-key' => 'test-data-value'
        ];
    }
}
