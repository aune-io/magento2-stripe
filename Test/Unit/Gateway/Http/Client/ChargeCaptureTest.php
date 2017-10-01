<?php

namespace Aune\Stripe\Test\Unit\Gateway\Http\Client;

use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Aune\Stripe\Gateway\Http\Client\ChargeCapture;
use Aune\Stripe\Gateway\Request\ChargeCaptureDataBuilder;
use Aune\Stripe\Model\Adapter\StripeAdapter;

class ChargeCaptureTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ChargeCapture
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

        $this->model = new ChargeCapture($criticalLoggerMock, $this->loggerMock, $this->adapter);
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
                    'client' => ChargeCapture::class,
                    'response' => []
                ]
            );

        $this->adapter->expects($this->once())
            ->method('chargeCapture')
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
        $response = $this->getResponseObject();

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => $this->getTransferData(),
                    'client' => ChargeCapture::class,
                    'response' => ['success' => 1],
                ]
            );
        
        $data = $this->getTransferData();
        $chargeId = $data[ChargeCaptureDataBuilder::CHARGE_ID];
        unset($data[ChargeCaptureDataBuilder::CHARGE_ID]);
        
        $this->adapter->expects($this->once())
            ->method('chargeCapture')
            ->with($this->getChargeId(), $data)
            ->willReturn($response);

        $actualResult = $this->model->placeRequest($this->getTransferObjectMock());

        $this->assertTrue(is_object($actualResult['object']));
        $this->assertEquals(['object' => $response], $actualResult);
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
    private function getResponseObject()
    {
        $obj = new \stdClass;
        $obj->success = true;

        return $obj;
    }

    /**
     * @return array
     */
    private function getTransferData()
    {
        return [
            ChargeCaptureDataBuilder::CHARGE_ID => $this->getChargeId(),
            'test-data-key' => 'test-data-value'
        ];
    }
    
    private function getChargeId()
    {
        return 'nfkuc4bvFynV8c62zCsEckeL';
    }
}
