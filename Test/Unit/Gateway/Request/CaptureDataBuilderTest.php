<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\CaptureDataBuilder;
use Aune\Stripe\Observer\DataAssignObserver;

class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaptureDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new CaptureDataBuilder();
    }

    /**
     * @covers \Aune\Stripe\Gateway\Request\CaptureDataBuilder::build
     */
    public function testBuild()
    {
        $expectedResult = [
            CaptureDataBuilder::CAPTURE  => true,
        ];

        static::assertEquals(
            $expectedResult,
            $this->builder->build([])
        );
    }
}
