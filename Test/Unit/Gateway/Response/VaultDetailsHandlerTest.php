<?php

namespace Aune\Stripe\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Response\VaultDetailsHandler;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class VaultDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_METHOD_ID = 'pm_123';
    
    /**
     * @var \Aune\Stripe\Gateway\Response\VaultDetailsHandler
     */
    private $handler;
    
    /**
     * @var PaymentTokenInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentExtensionFactory;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReader;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    protected function setUp()
    {
        $this->paymentTokenFactory = $this->getMockBuilder(PaymentTokenInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->paymentExtensionFactory = $this->getMockBuilder(OrderPaymentExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new VaultDetailsHandler(
            $this->paymentTokenFactory,
            $this->paymentExtensionFactory,
            $this->config,
            $this->subjectReaderMock
        );
    }

    /**
     * @covers \Aune\Stripe\Gateway\Response\VaultDetailsHandler::handle
     */
    public function testHandle()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $paymentIntent = $this->getStripePaymentIntent();

        $subject = ['payment' => $paymentData];
        $response = ['object' => $paymentIntent];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPaymentIntent')
            ->with($response)
            ->willReturn($paymentIntent);
        
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->paymentTokenFactory->expects(self::once())
            ->method('create')
            ->willReturn($paymentToken);
        
        $paymentExtension = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setVaultPaymentToken'])
            ->getMock();
        
        $this->paymentExtensionFactory->expects(self::once())
            ->method('create')
            ->willReturn($paymentExtension);
        
        $paymentExtension->expects(self::once())
            ->method('setVaultPaymentToken')
            ->with($paymentToken);

        $this->config->expects(self::once())
            ->method('getCctypesMapper')
            ->willReturn(['visa' => 'VI']);
        
        $paymentToken->expects(self::once())
            ->method('setTokenDetails')
            ->with('{"tokenType":"source","type":"VI","maskedCC":1234,"expirationDate":"07\/2029"}');

        $paymentToken->expects(self::once())
            ->method('setGatewayToken')
            ->with(self::PAYMENT_METHOD_ID);

        $paymentToken->expects(self::once())
            ->method('setExpiresAt')
            ->with('2029-08-01 00:00:00');

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
     * Create Stripe Payment Intent
     * 
     * @return \Stripe\PaymentIntent
     */
    private function getStripePaymentIntent()
    {
        $attributes = [
            'object' => 'payment_intent',
            'payment_method' => self::PAYMENT_METHOD_ID,
            'charges' => [
                'object' => 'list',
                'data' => [[
                    'object' => 'charge',
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'Visa',
                            'exp_month' => 07,
                            'exp_year' => 29,
                            'last4' => 1234,
                        ]
                    ]
                ]]
            ]
        ];
        
        return \Stripe\Util\Util::convertToStripeObject($attributes, []);
    }
}
