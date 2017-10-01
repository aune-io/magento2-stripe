<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Request\SourceDataBuilder;
use Aune\Stripe\Model\Adapter\StripeAdapter;
use Aune\Stripe\Observer\DataAssignObserver;

class SourceDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const SOURCE = 'sourceId';
    const CUSTOMER = 'customerId';

    /**
     * @var SourceDataBuilder
     */
    private $builder;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var StripeAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $adapter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;

    /**
     * @var OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;
    
    /**
     * @var AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressMock;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockForAbstractClass(OrderAdapterInterface::class);
        $this->addressMock = $this->getMockForAbstractClass(AddressAdapterInterface::class);

        $this->builder = new SourceDataBuilder(
            $this->configMock,
            $this->subjectReaderMock,
            $this->adapter
        );
    }

    public function testBuild()
    {
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            SourceDataBuilder::SOURCE  => self::SOURCE,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(static::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    public function testBuildStoreCustomer()
    {
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            SourceDataBuilder::CUSTOMER  => self::CUSTOMER,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->configMock->expects(self::once())
            ->method('getStoreCustomer')
            ->willReturn(true);
        
        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();

        $this->adapter->expects(static::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($this->getCustomerObject());

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    public function testBuildVault()
    {
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            SourceDataBuilder::CUSTOMER  => self::CUSTOMER,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(static::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();

        $this->adapter->expects(static::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($this->getCustomerObject());

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * @return array
     */
    private function prepareCustomerRequest()
    {
        $email = 'customer@example.com';
        $firstname = 'Name';
        $lastname = 'Surname';
        
        $this->addressMock->expects(static::once())
            ->method('getEmail')
            ->willReturn($email);
        
        $this->addressMock->expects(static::once())
            ->method('getFirstname')
            ->willReturn($firstname);
        
        $this->addressMock->expects(static::once())
            ->method('getLastname')
            ->willReturn($lastname);
        
        return [
            'email' => $email,
            'description' => $firstname . ' ' . $lastname,
            'source' => self::SOURCE,
        ];
    }
    

    /**
     * @return \stdClass
     */
    private function getCustomerObject()
    {
        $obj = new \stdClass;
        $obj->id = self::CUSTOMER;

        return $obj;
    }
}
