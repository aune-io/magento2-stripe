<?php

namespace Aune\Stripe\Gateway\Response;

use \DateTime;
use \DateInterval;
use \DateTimeZone;
use Stripe\PaymentIntent;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;

use Aune\Stripe\Gateway\Config\Config;
use Aune\Stripe\Gateway\Helper\SubjectReader;
use Aune\Stripe\Gateway\Helper\TokenProvider;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config,
        SubjectReader $subjectReader
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $paymentIntent = $this->subjectReader->readPaymentIntent($response);
        $payment = $paymentDO->getPayment();

        // add vault payment token entity to extension attributes
        $paymentToken = $this->getVaultPaymentToken($paymentIntent);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken(PaymentIntent $paymentIntent)
    {
        // Extract source id as token
        $token = $paymentIntent->payment_method;
        if (empty($token)) {
            return null;
        }

        $charge = $paymentIntent->charges->data[0];
        $card = $charge->payment_method_details->card;

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $expirationDate = $this->getExpirationDate($card);
        
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'tokenType' => TokenProvider::TOKEN_TYPE_SOURCE,
            'type' => $this->getCreditCardType($card->brand),
            'maskedCC' => $card->last4,
            'expirationDate' => $expirationDate->format('m/Y'),
        ]));
        
        $expirationDate->add(new DateInterval('P1M'));

        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($expirationDate->format('Y-m-d 00:00:00'));

        return $paymentToken;
    }

    /**
     * @param object $card
     * @return \DateTime
     */
    private function getExpirationDate($card)
    {
        return new DateTime(
            $card->exp_year
            . '-'
            . $card->exp_month
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new DateTimeZone('UTC')
        );
    }

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = json_encode($details);
        return $json ? $json : '{}';
    }

    /**
     * Get type of credit card mapped from Stripe
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCctypesMapper();

        return $mapper[$replaced];
    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
