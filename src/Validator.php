<?php

declare(strict_types=1);


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Validator implements MiddlewareInterface
{
    /**
     * @var array<string, string>
     */
    protected array $rules;

    /**
     * @param array<string, string> $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        if (empty($this->rules)) {
            $request = $request->withAttribute('error', 'No rules set.');
        }

        return $handler->handle($request);
    }

    /**
     * @param array<string, string> $rules
     * @param array<string, mixed> $data
     * @return bool
     */
    protected function validate(array $rules, array $data): bool
    {
        if (empty($data) || count($rules) !== count($data)) {
            return false;
        }

        return true;
    }
}