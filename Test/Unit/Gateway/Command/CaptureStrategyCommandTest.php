<?php

namespace Aune\Stripe\Test\Unit\Gateway\Command;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Command\CaptureStrategyCommand;

class CaptureStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CommandPoolInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $commandPoolMock;
    
    /**
     * @var TransactionRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionRepositoryMock;
    
    /**
     * @var FilterBuilder|PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilderMock;
    
    /**
     * @var SearchCriteriaBuilder|PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;
    
    /**
     * @var DateTime|PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;
    
    /**
     * @var SubjectReader|PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;
    
    /**
     * @var CaptureStrategyCommand
     */
    private $captureStrategyCommand;
    
    /**
     * @var PaymentDataObjectInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;
    
    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;
    
    /**
     * @var SearchCriteriaInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaMock;
    
    /**
     * @var TransactionSearchResultInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionSearchResultMock;
    
    /**
     * @var CommandInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $commandMock;
    
    /**
     * @var Order|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;
    
    protected function setUp()
    {
        $this->commandPoolMock = $this->getMockForAbstractClass(CommandPoolInterface::class);
        $this->transactionRepositoryMock = $this->getMockForAbstractClass(TransactionRepositoryInterface::class);
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->paymentDOMock = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaMock = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->transactionSearchResultMock = $this->getMockForAbstractClass(TransactionSearchResultInterface::class);
        $this->commandMock = $this->getMockForAbstractClass(CommandInterface::class);
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->captureStrategyCommand = new CaptureStrategyCommand(
            $this->commandPoolMock,
            $this->transactionRepositoryMock,
            $this->filterBuilderMock,
            $this->searchCriteriaBuilderMock,
            $this->dateTimeMock,
            $this->subjectReaderMock
        );
    }

    /**
     * Capture with vaulted details
     * 
     * @covers \Aune\Stripe\Gateway\Command\CaptureStrategyCommand::execute
     */
    public function testExecuteVaultCapture()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setField')
            ->willReturn($this->filterBuilderMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setValue')
            ->willReturn($this->filterBuilderMock);
        
        $this->searchCriteriaBuilderMock->expects(self::once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        
        $this->transactionRepositoryMock->expects(self::once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->transactionSearchResultMock);
        
        $this->transactionSearchResultMock->expects(self::once())
            ->method('getTotalCount')
            ->willReturn(0);
        
        $this->paymentMock->expects(self::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(true);
        
        $this->paymentMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $tsCreatedAt = time() - CaptureStrategyCommand::AUTHORIZATION_TTL - 10;
        $createdAt = date('Y-m-d H:i:s', $tsCreatedAt);
        
        $this->orderMock->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn($createdAt);
        
        $this->dateTimeMock->expects(self::exactly(2))
            ->method('timestamp')
            ->withConsecutive([], [$createdAt])
            ->willReturnOnConsecutiveCalls(time(), $tsCreatedAt);
        
        $this->commandPoolMock->expects(self::once())
            ->method('get')
            ->with(CaptureStrategyCommand::CAPTURE)
            ->willReturn($this->commandMock);
        
        $this->commandMock->expects(self::once())
            ->method('execute')
            ->with($subject)
            ->willReturn(true);
        
        $this->captureStrategyCommand->execute($subject);
    }
    
    /**
     * Capture authorizated transaction
     * 
     * @covers \Aune\Stripe\Gateway\Command\CaptureStrategyCommand::execute
     */
    public function testExecuteCaptureAuthorization()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setField')
            ->willReturn($this->filterBuilderMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setValue')
            ->willReturn($this->filterBuilderMock);
        
        $this->searchCriteriaBuilderMock->expects(self::once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        
        $this->transactionRepositoryMock->expects(self::once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->transactionSearchResultMock);
        
        $this->transactionSearchResultMock->expects(self::once())
            ->method('getTotalCount')
            ->willReturn(0);
        
        $this->paymentMock->expects(self::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(true);
        
        $this->paymentMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $tsCreatedAt = time() - 10;
        $createdAt = date('Y-m-d H:i:s', $tsCreatedAt);
        
        $this->orderMock->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn($createdAt);
        
        $this->dateTimeMock->expects(self::exactly(2))
            ->method('timestamp')
            ->withConsecutive([], [$createdAt])
            ->willReturnOnConsecutiveCalls(time(), $tsCreatedAt);
        
        $this->commandPoolMock->expects(self::once())
            ->method('get')
            ->with(CaptureStrategyCommand::CAPTURE)
            ->willReturn($this->commandMock);
        
        $this->commandMock->expects(self::once())
            ->method('execute')
            ->with($subject)
            ->willReturn(true);
        
        $this->captureStrategyCommand->execute($subject);
    }
    
    /**
     * Perform a sale
     * 
     * @covers \Aune\Stripe\Gateway\Command\CaptureStrategyCommand::execute
     */
    public function testExecuteSale()
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($this->paymentDOMock);
        
        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setField')
            ->willReturn($this->filterBuilderMock);
        
        $this->filterBuilderMock->expects(self::exactly(2))
            ->method('setValue')
            ->willReturn($this->filterBuilderMock);
        
        $this->searchCriteriaBuilderMock->expects(self::once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        
        $this->transactionRepositoryMock->expects(self::once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->transactionSearchResultMock);
        
        $this->transactionSearchResultMock->expects(self::once())
            ->method('getTotalCount')
            ->willReturn(0);
        
        $this->paymentMock->expects(self::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);
        
        $this->commandPoolMock->expects(self::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SALE)
            ->willReturn($this->commandMock);
        
        $this->commandMock->expects(self::once())
            ->method('execute')
            ->with($subject)
            ->willReturn(true);
        
        $this->captureStrategyCommand->execute($subject);
    }
}
