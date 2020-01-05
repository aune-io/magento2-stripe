<?php

namespace Aune\Stripe\Controller\PaymentIntent;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Aune\Stripe\Gateway\Command\CreatePaymentIntentCommand;

class Generate extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CreatePaymentIntentCommand
     */
    private $command;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param CreatePaymentIntentCommand $command
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CreatePaymentIntentCommand $command
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->command = $command;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $publicHash = $this->getRequest()->getParam('public_hash');

            $paymentIntent = $this->command->execute([
                'quote' => $this->checkoutSession->getQuote(),
                'customer_id' => $this->customerSession->getCustomerId(),
                'public_hash' => $publicHash,
            ]);

            $response->setData(['paymentIntent' => [
                'id' => $paymentIntent->id,
                'clientSecret' => $paymentIntent->client_secret,
            ]]);

        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->processBadRequest($response);
        }

        return $response;
    }

    /**
     * Return response for bad request
     * @param ResultInterface $response
     * @return ResultInterface
     */
    private function processBadRequest(ResultInterface $response)
    {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $response->setData(['message' => __('Sorry, but something went wrong')]);

        return $response;
    }
}
