<?php

namespace Aune\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Helper\TokenProvider;
use Aune\Stripe\Gateway\Request\SourceDataBuilder;
use Aune\Stripe\Model\Adapter\StripeAdapter;
use Aune\Stripe\Observer\DataAssignObserver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CUSTOMER_ID = 'cus_123';
    const SOURCE_ID = 'src_123';

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
     * @var TokenProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenProviderMock;

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
        $this->tokenProviderMock = $this->getMockBuilder(TokenProvider::class)
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
            $this->adapter,
            $this->tokenProviderMock
        );
    }

    /**
     * Tests source data builder with no tokenization
     * 
     * @covers \Aune\Stripe\Gateway\Request\SourceDataBuilder::build
     */
    public function testBuild()
    {
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            SourceDataBuilder::SOURCE  => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * Tests source data builder with customer store enabled
     * 
     * @covers \Aune\Stripe\Gateway\Request\SourceDataBuilder::build
     */
    public function testBuildStoreCustomer()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            SourceDataBuilder::CUSTOMER  => self::CUSTOMER_ID,
            SourceDataBuilder::SOURCE  => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
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
            ->method('isStoreCustomerEnabled')
            ->willReturn(true);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(false);

        $this->orderMock->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();
        $stripeCustomer = $this->getCustomerObject();

        $this->adapter->expects(self::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($stripeCustomer);

        $this->adapter->expects(self::once())
            ->method('customerAttachSource')
            ->with($stripeCustomer, self::SOURCE_ID);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * Tests source data builder with vault enabled
     * 
     * @covers \Aune\Stripe\Gateway\Request\SourceDataBuilder::build
     */
    public function testBuildVault()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            SourceDataBuilder::CUSTOMER  => self::CUSTOMER_ID,
            SourceDataBuilder::SOURCE  => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(false);

        $this->orderMock->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();
        $stripeCustomer = $this->getCustomerObject();

        $this->adapter->expects(self::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($stripeCustomer);

        $this->adapter->expects(self::once())
            ->method('customerAttachSource')
            ->with($stripeCustomer, self::SOURCE_ID);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Tests source data builder with vault enabled on guest order
     * 
     * @covers \Aune\Stripe\Gateway\Request\SourceDataBuilder::build
     */
    public function testBuildVaultGuest()
    {
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            SourceDataBuilder::SOURCE  => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(null);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Tests source data builder with vault enabled and customer already
     * saved in Stripe
     * 
     * @covers \Aune\Stripe\Gateway\Request\SourceDataBuilder::build
     */
    public function testBuildVaultExisting()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::SOURCE, self::SOURCE_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            SourceDataBuilder::CUSTOMER  => self::CUSTOMER_ID,
            SourceDataBuilder::SOURCE  => self::SOURCE_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(self::CUSTOMER_ID);
        
        $stripeCustomer = $this->getCustomerObject();
        
        $this->adapter->expects(self::once())
            ->method('customerRetrieve')
            ->with(self::CUSTOMER_ID)
            ->willReturn($stripeCustomer);
        
        $this->adapter->expects(self::once())
            ->method('customerAttachSource')
            ->with($stripeCustomer, self::SOURCE_ID);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Prepare Stripe customer creation request
     * 
     * @return array
     */
    private function prepareCustomerRequest()
    {
        $email = 'customer@example.com';
        $firstname = 'Name';
        $lastname = 'Surname';
        
        $this->addressMock->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);
        
        $this->addressMock->expects(self::once())
            ->method('getFirstname')
            ->willReturn($firstname);
        
        $this->addressMock->expects(self::once())
            ->method('getLastname')
            ->willReturn($lastname);
        
        return [
            'email' => $email,
            'description' => $firstname . ' ' . $lastname,
        ];
    }
    

    /**
     * Create mock Stripe customer object
     * 
     * @return \stdClass
     */
    private function getCustomerObject()
    {
        return \Stripe\Util\Util::convertToStripeObject([
            'object' => 'customer',
            'id' => self::CUSTOMER_ID,
        ], []);
    }
}
