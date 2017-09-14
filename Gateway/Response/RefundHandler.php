<?php

namespace Aune\Stripe\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Aune\Stripe\Gateway\Helper\SubjectReader;

class RefundHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        
        /** @var Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();
        
        if ($orderPayment instanceof Payment) {
            /** @var \Stripe\Refund $refund */
            $refund = $this->subjectReader->readRefund($response);

            $this->setRefundId(
                $orderPayment,
                $refund
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $orderPayment->setShouldCloseParentTransaction(
                $this->shouldCloseParentTransaction($orderPayment)
            );
        }
    }

    /**
     * @param Payment $orderPayment
     * @param \Stripe\Refund $refund
     * @return void
     */
    protected function setRefundId(
        Payment $orderPayment,
        \Stripe\Refund $refund
    ) {
        $orderPayment->setTransactionId($refund->id);
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return true;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        $creditmemo = $orderPayment->getCreditmemo();
        if (!$creditmemo || !($invoice = $creditmemo->getInvoice())) {
            return true;
        }
        
        return !(bool)$invoice->canRefund();
    }
}
