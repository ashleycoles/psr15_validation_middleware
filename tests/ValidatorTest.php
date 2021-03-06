<?php

declare(strict_types=1);

require_once 'src/Validator.php';

use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use ValidationMiddleware\Validator;

class ValidatorTest extends TestCase
{
    private ServerRequestInterface $serverRequest;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $factory = Factory::getServerRequestFactory();
        $this->serverRequest = $factory->createServerRequest('POST', '/');
    }

    /**
     * Helper to temporarily test protected methods.
     *
     * This test suite should only test public behaviours, so specific implementation tests
     * of protected/private methods should be removed once the method is finished.
     *
     * @throws ReflectionException
     */
    protected static function getMethod($name): ReflectionMethod
    {
        $class = new ReflectionClass(Validator::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function provider_validValidators(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'name' => 'string',
                    'online' => 'boolean',
                    'lifetime' => 'double',
                    'friends' => 'array'
                ],
                [
                    'id' => 1,
                    'name' => 'steve',
                    'online' => true,
                    'lifetime' => 12.7654,
                    'friends' => []
                ],
            ],
        ];
    }

    public function provider_emptyDataValidators(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                ],
                [],
            ],
        ];
    }

    public function provider_tooMuchDataValidators(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                ],
                [
                    'id' => 1,
                    'name' => 'a',
                    'rate' => 12.4
                ],
            ],
        ];
    }

    public function provider_tooLittleDataValidators(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'name' => 'string'
                ],
                [
                    'id' => 1
                ],
            ],
        ];
    }

    public function provider_multipleIncorrectTypes(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'name' => 'string',
                    'active' => 'boolean',
                    'friends' => 'array',
                    'test' => 'string'
                ],
                [
                    'id' => 'a',
                    'name' => [],
                    'active' => 'hello',
                    'friends' => false,
                    'test' => 'testing'
                ],
            ],
        ];
    }


    public function provider_mismatchedDataValidators(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'id2' => 'integer',
                ],
                [
                    'id3' => 'a',
                    'id4' => 'b'
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_validatorProcess_emptyRules(): void
    {
        $middleWare = new Validator([]);

        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $this->assertEquals(
                    ['No rules set.'],
                    $request->getAttribute('errors')
                );
            }
        ], $this->serverRequest);
    }

    /**
     * @dataProvider provider_validValidators
     * @param array $validators
     * @param array $data
     */
    public function test_validatorProcess_validData(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);

        Dispatcher::run([
            $middleWare,
            function (RequestInterface $request) {
                $this->assertEmpty($request->getAttribute('errors'));
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider provider_emptyDataValidators
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorProcess_emptyData(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);
        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $expected = ['id: Required field missing.'];
                $this->assertEquals($expected, $request->getAttribute('errors'));
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider provider_tooMuchDataValidators
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorProcess_tooMuchData(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);
        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $expected = ['name: Does not match data format.', 'rate: Does not match data format.'];
                $this->assertEquals($expected, $request->getAttribute('errors'));
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider provider_tooLittleDataValidators
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorProcess_tooLittleData(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);
        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $expected = ['name: Required field missing.'];
                $this->assertEquals($expected, $request->getAttribute('errors'));
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider provider_multipleIncorrectTypes
     * @param array $validators
     * @param array $data
     * @return void
     */
    public function test_validatorProcess_withMultipleIncorrectTypes(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);

        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $this->assertEquals(
                    [
                        'id: Must be of type integer.',
                        'name: Must be of type string.',
                        'active: Must be of type boolean.',
                        'friends: Must be of type array.',
                    ],
                    $request->getAttribute('errors')
                );
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider provider_mismatchedDataValidators
     * @param array $validators
     * @param array $data
     * @return void
     */
    public function test_validatorProcess_withMismatchedData(array $validators, array $data): void
    {
        $requestWithData = $this->serverRequest->withParsedBody($data);
        $middleWare = new Validator($validators);
        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $expected = [
                    'id: Required field missing.',
                    'id2: Required field missing.',
                    'id3: Does not match data format.',
                    'id4: Does not match data format.'
                ];
                $this->assertEquals($expected, $request->getAttribute('errors'));
            }
        ], $requestWithData);
    }
}