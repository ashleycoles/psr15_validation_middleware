<?php

declare(strict_types=1);

require_once 'src/Validator.php';

use GuzzleHttp\Psr7\ServerRequest;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class ValidatorTest extends TestCase
{

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

    public function test_validatorWithEmptyRules()
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
    public function test_validatorWithValidData(array $validators, array $data)
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
}