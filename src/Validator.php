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

    protected array $errors;

    protected ServerRequestInterface $request;

    protected bool $valid = true;

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
            $this->addError('No rules set.');
            $request = $request->withAttribute('errors', $this->errors);
            return $handler->handle($request);
        }

        $this->request = $request;

        $data = $this->request->getParsedBody();

        if (!$this->validate($this->rules, $data)) {
            $request = $request->withAttribute('errors', $this->errors);
            return $handler->handle($request);
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

        foreach ($rules as $field => $type) {
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
}