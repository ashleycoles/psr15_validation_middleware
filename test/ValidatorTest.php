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
    /**
     * @throws ReflectionException
     */
    protected static function getMethod($name): ReflectionMethod
    {
        $class = new ReflectionClass(Validator::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function validValidatorsProvider(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'name' => 'string',
                    'online' => false,
                    'lifetime' => 'double'
                ],
                [
                    'id' => 1,
                    'name' => 'steve',
                    'online' => 'boolean',
                    'lifetime' => 12.7654
                ],
            ],
        ];
    }

    public function emptyDataValidatorsProvider(): array
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

    public function tooMuchDataValidatorsProvider(): array
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

    public function tooLittleDataValidatorsProvider(): array
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

    public function notIntDataValidatorsProvider(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                ],
                [
                    'id' => 'a'
                ],
            ],
        ];
    }

    public function twoNotIntsDataValidatorsProvider(): array
    {
        return [
            [
                [
                    'id' => 'integer',
                    'id2' => 'integer',
                ],
                [
                    'id' => 'a',
                    'id2' => 'b'
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_validatorWithEmptyRules(): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $middleWare = new Validator([]);

        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $this->assertEquals(
                    ['No rules set.'],
                    $request->getAttribute('errors')
                );
            }
        ], $request);
    }

    /**
     * @dataProvider validValidatorsProvider
     * @param array $validators
     * @param array $data
     */
    public function test_validatorWithValidData(array $validators, array $data): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $requestWithData = $request->withParsedBody($data);
        $middleWare = new Validator($validators);

        Dispatcher::run([
            $middleWare,
            function (RequestInterface $request) {
                $this->assertInstanceOf(ServerRequestInterface::class, $request);
            }
        ], $requestWithData);
    }


    /**
     * @dataProvider emptyDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorValidateMethod_emptyData(array $validators, array $data): void
    {
        $validateMethod = self::getMethod('validate');
        $middleWare = new Validator($validators);
        $valid = $validateMethod->invoke($middleWare, $validators, $data);
        $this->assertFalse($valid);
    }

    /**
     * @dataProvider tooMuchDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorProcess_tooMuchData(array $validators, array $data): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $requestWithData = $request->withParsedBody($data);
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
     * @dataProvider tooLittleDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorValidateMethod_tooLittleData(array $validators, array $data): void
    {
        $validateMethod = self::getMethod('validate');
        $middleWare = new Validator($validators);
        $valid = $validateMethod->invoke($middleWare, $validators, $data);
        $this->assertFalse($valid);
    }

    /**
     * @dataProvider notIntDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     * @throws ReflectionException
     */
    public function test_validatorValidateMethod_singleDataNotInt(array $validators, array $data): void
    {
        $validateMethod = self::getMethod('validate');
        $middleWare = new Validator($validators);
        $valid = $validateMethod->invoke($middleWare, $validators, $data);
        $this->assertFalse($valid);
    }

    /**
     * @dataProvider notIntDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     */
    public function test_validatorProcess_withInvalidSingleInt(array $validators, array $data): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $requestWithData = $request->withParsedBody($data);
        $middleWare = new Validator($validators);

        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $this->assertEquals(
                    ['id: Must be of type integer'],
                    $request->getAttribute('errors')
                );
            }
        ], $requestWithData);
    }

    /**
     * @dataProvider twoNotIntsDataValidatorsProvider
     * @param array $validators
     * @param array $data
     * @return void
     */
    public function test_validatorProcess_withInvalidTwoInts(array $validators, array $data): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $requestWithData = $request->withParsedBody($data);
        $middleWare = new Validator($validators);
        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $expected = ['id: Must be of type integer', 'id2: Must be of type integer'];
                $this->assertEquals($expected, $request->getAttribute('errors'));
            }
        ], $requestWithData);
    }
}