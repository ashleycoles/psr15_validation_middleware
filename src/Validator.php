<?php

declare(strict_types=1);

namespace ValidationMiddleware;

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
        if ((!is_array($data))) {
            return false;
        }

        $valid = true;

        $ruleDataComparison = count($this->rules) <=> count($data);

        // More rules than data items
        if ($ruleDataComparison === 1) {
            $missingDataItems = array_diff_key($this->rules, $data);
            foreach ($missingDataItems as $field => $item) {
                $this->addError("$field: Required field missing.");
            }
            return false;
        }

        // More data than rules
        if ($ruleDataComparison === -1) {
            $extraDataItems = array_diff_key($data, $this->rules);

            foreach ($extraDataItems as $field => $item) {
                $this->addError("$field: Does not match data format.");
                $valid = false;
            }
            return $valid;
        }

        foreach ($this->rules as $ruleField => $ruleType) {
            if (!array_key_exists($ruleField, $data)) {
                $this->addError("$ruleField: Field missing.");
                $valid = false;
            }
        }

        foreach ($data as $dataField => $dataValue) {
            if (!array_key_exists($dataField, $this->rules)) {
                $this->addError("$dataField: Does not match data format.");
                $valid = false;
            }
        }

        // Equal number of data items
        foreach ($this->rules as $field => $type) {
            if (isset($data[$field]) && gettype($data[$field]) !== $type) {
                $this->addError("$field: Must be of type $type");
                $valid = false;
            }
        }
        return $valid;
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
