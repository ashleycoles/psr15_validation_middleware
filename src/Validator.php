<?php

declare(strict_types=1);

namespace ValidationMiddleware;

use http\Env\Request;
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
     * @var array<string>
     */
    protected array $errors = [];

    protected ServerRequestInterface $request;

    protected bool $valid = true;

    protected RequestHandlerInterface $handler;

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
    ): ResponseInterface {

        $this->request = $request;
        $this->handler = $handler;

        if (empty($this->rules)) {
            $this->addError('No rules set.');
            return $this->handleRequest();
        }

        $data = $this->request->getParsedBody();

        $this->validate($data);

        return $this->handleRequest();
    }

    /**
     * @param object|array<string, string>|null $data
     * @return bool
     */
    protected function validate(object|array|null $data): bool
    {
        if ((!is_array($data)) || empty($data) || count($this->rules) !== count($data)) {
            return false;
        }

        foreach ($this->rules as $field => $type) {
            if (gettype($data[$field]) !== $type) {
                $this->addError("$field: Must be of type $type");
                $this->valid = false;
            }
        }

        return $this->valid;
    }

    protected function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    protected function handleRequest(): ResponseInterface
    {
        $request = $this->request->withAttribute('errors', $this->errors);
        return $this->handler->handle($request);
    }
}
