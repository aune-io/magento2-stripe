<?php

namespace Aune\Stripe\Test\Unit\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Aune\Stripe\Gateway\Http\TransferFactory;

class TransferFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var TransferFactory
     */
    private $transferMock;

    /**
     * @var TransferBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transferBuilder;

    protected function setUp()
    {
        $this->transferBuilder = $this->getMock(TransferBuilder::class);
        $this->transferMock = $this->getMock(TransferInterface::class);

        $this->transferFactory = new TransferFactory(
            $this->transferBuilder
        );
    }

    public function testCreate()
    {
        $request = ['data1', 'data2'];

        $this->transferBuilder->expects($this->once())
            ->method('setBody')
            ->with($request)
            ->willReturnSelf();

        $this->transferBuilder->expects($this->once())
            ->method('build')
            ->willReturn($this->transferMock);

        $this->assertEquals($this->transferMock, $this->transferFactory->create($request));
    }
}
