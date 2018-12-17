<?php

namespace Aune\Stripe\Test\Unit\Gateway\Validator;

use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Aune\Stripe\Gateway\Validator\ResponseValidator;
use Aune\Stripe\Gateway\Helper\SubjectReader;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ResponseValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @var ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultInterfaceFactory;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReader;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resultInterfaceFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseValidator = new ResponseValidator(
            $this->resultInterfaceFactory,
            $this->subjectReader
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateReadResponseException()
    {
        $validationSubject = [
            'response' => null
        ];

        $this->subjectReader->expects(self::once())
            ->method('readResponseObject')
            ->with($validationSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->responseValidator->validate($validationSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateReadResponseObjectException()
    {
        $validationSubject = [
            'response' => ['object' => null]
        ];

        $this->subjectReader->expects(self::once())
            ->method('readResponseObject')
            ->with($validationSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->responseValidator->validate($validationSubject);
    }

    /**
     * Run test for validate method
     *
     * @param array $validationSubject
     * @param bool $isValid
     * @param Phrase[] $messages
     * @return void
     *
     * @dataProvider dataProviderTestValidate
     */
    public function testValidate(array $validationSubject, $isValid, $messages)
    {
        $result = $this->getMockForAbstractClass(ResultInterface::class);

        $this->subjectReader->expects(self::once())
            ->method('readResponseObject')
            ->with($validationSubject)
            ->willReturn($validationSubject['response']['object']);

        $this->resultInterfaceFactory->expects(self::once())
            ->method('create')
            ->with([
                'isValid' => $isValid,
                'failsDescription' => $messages,
                'errorCodes' => [],
            ])
            ->willReturn($result);

        $actual = $this->responseValidator->validate($validationSubject);

        self::assertEquals($result, $actual);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        $successTrue = \Stripe\Util\Util::convertToStripeObject([
            'object' => 'charge',
            'status' => 'succeeded',
        ], []);

        $transactionDeclined = \Stripe\Util\Util::convertToStripeObject([
            'object' => 'charge',
            'status' => 'failed',
        ], []);

        $errorResult = new \Stripe\Error\Authentication('');

        return [
            [
                'validationSubject' => [
                    'response' => [
                        'object' => $successTrue
                    ],
                ],
                'isValid' => true,
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'object' => $transactionDeclined
                    ]
                ],
                'isValid' => false,
                [
                    __('Wrong transaction status')
                ]
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'object' => $errorResult,
                    ]
                ],
                'isValid' => false,
                [
                    __('Stripe error response'),
                    __('Wrong transaction status')
                ]
            ]
        ];
    }
}
