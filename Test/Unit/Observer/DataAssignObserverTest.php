<?php

namespace Aune\Stripe\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Aune\Stripe\Observer\DataAssignObserver;

class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{
    const SOURCE = 'fwtYU5z5E6e6Dgav8BBUvtqB';

    /**
     * @var Observer|PHPUnit_Framework_MockObject_MockObject
     */
    private $observerContainer;
    
    /**
     * @var Event|PHPUnit_Framework_MockObject_MockObject
     */
    private $event;
    
    /**
     * @var InfoInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentInfoModel;

    protected function setUp()
    {
        $this->observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->paymentInfoModel = $this->getMockForAbstractClass(InfoInterface::class);
    }

    /**
     * @covers \Aune\Stripe\Observer\DataAssignObserver::execute
     */
    public function testExecute()
    {
        $dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'source' => self::SOURCE,
                ]
            ]
        );
        
        $this->observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);
        
        $this->event->expects(static::exactly(2))
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::MODEL_CODE, $this->paymentInfoModel],
                    [AbstractDataAssignObserver::DATA_CODE, $dataObject]
                ]
            );
        
        $this->paymentInfoModel->expects(static::at(0))
            ->method('setAdditionalInformation')
            ->with('source', self::SOURCE);

        $observer = new DataAssignObserver();
        $observer->execute($this->observerContainer);
    }
    
    /**
     * @covers \Aune\Stripe\Observer\DataAssignObserver::execute
     */
    public function testExecuteNoAdditionalData()
    {
        $dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => false
            ]
        );
        
        $this->observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);
        
        $this->event->expects(static::once())
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::DATA_CODE, $dataObject]
                ]
            );

        $observer = new DataAssignObserver();
        $observer->execute($this->observerContainer);
    }
}
