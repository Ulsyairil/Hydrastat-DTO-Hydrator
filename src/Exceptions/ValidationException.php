<?php

namespace Hydrastat\DtoHydrator\Exceptions;

use Hydrastat\DtoHydrator\Core\ValidationResult;
use Exception;

class ValidationException extends Exception
{
    public function __construct(
        public readonly ValidationResult $result,
        string $message = 'Validation failed',
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResult(ValidationResult $result, string $message = 'Validation failed'): self
    {
        return new self($result, $message);
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->result->getErrors();
    }

    public function firstError(string $field): ?string
    {
        return $this->result->getFirstError($field);
    }
}