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
     * Validates an assoc array of data against an array of rules
     *
     * @param object|array<string, string>|null $data
     * @return bool
     */
    protected function validate(object|array|null $data): bool
    {
        if ((!is_array($data)) || empty($data)) {
            return false;
        }

        if (count($this->rules) !== count($data)) {
            if (count($data) < count($this->rules)) {
                $this->valid = false;
                $missingDataItems = array_diff_key($this->rules, $data);
                foreach ($missingDataItems as $field => $item) {
                    $this->addError("$field: Required field missing.");
                }
                return $this->valid;
            }

            $extraDataItems = array_diff_key($data, $this->rules);

            foreach ($extraDataItems as $field => $item) {
                $this->addError("$field: Does not match data format.");
                $this->valid = false;
            }
            return $this->valid;
        }

        foreach ($this->rules as $field => $type) {
            if (gettype($data[$field]) !== $type) {
                $this->addError("$field: Must be of type $type");
                $this->valid = false;
            }
        }

        return $this->valid;
    }

    /**
     * Adds a validation error message
     *
     * @param string $error
     * @return void
     */
    protected function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Gather errors (if present) and handles the request
     *
     * @return ResponseInterface
     */
    protected function handleRequest(): ResponseInterface
    {
        if (!empty($this->errors)) {
            $this->request = $this->request->withAttribute('errors', $this->errors);
        }
        return $this->handler->handle($this->request);
    }
}
