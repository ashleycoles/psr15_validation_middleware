<?php

declare(strict_types=1);

require_once 'src/Validator.php';

use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

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
                    ['id' => 'int'],
                ],
                [
                    'id' => 1,
                ],
            ],
        ];
    }

    public function emptyDataValidatorsProvider(): array
    {
        return [
            [
                [
                    ['id' => 'int'],
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
                    ['id' => 'int'],
                ],
                [
                    'id' => 1,
                    'name' => 'a'
                ],
            ],
        ];
    }

    public function tooLittleDataValidatorsProvider(): array
    {
        return [
            [
                [
                    ['id' => 'int'],
                    ['name' => 'string']
                ],
                [
                    'id' => 1
                ],
            ],
        ];
    }

    public function test_validatorWithEmptyRules(): void
    {
        $factory = Factory::getServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $middleWare = new Validator([]);

        Dispatcher::run([
            $middleWare,
            function (ServerRequestInterface $request) {
                $this->assertEquals(
                    'No rules set.',
                    $request->getAttribute('error')
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
     * @dataProvider validValidatorsProvider
     * @param array $validators
     * @param array $data
     * @throws ReflectionException
     */
    public function test_validatorValidateMethod_validData(array $validators, array $data): void
    {
        $validateMethod = self::getMethod('validate');
        $middleWare = new Validator($validators);
        $valid = $validateMethod->invoke($middleWare, $validators, $data);
        $this->assertTrue($valid);
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
    public function test_validatorValidateMethod_tooMuchData(array $validators, array $data): void
    {
        $validateMethod = self::getMethod('validate');
        $middleWare = new Validator($validators);
        $valid = $validateMethod->invoke($middleWare, $validators, $data);
        $this->assertFalse($valid);
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


}