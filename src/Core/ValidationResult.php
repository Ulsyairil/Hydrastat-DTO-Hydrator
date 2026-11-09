<?php

namespace Hydrastat\DtoHydrator\Core;

class ValidationResult
{
    /** @var array<string, string[]> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validatedData = [];

    public function __construct(
        public readonly bool $valid = true,
        array $errors = [],
        array $validatedData = []
    ) {
        $this->errors = $errors;
        $this->validatedData = $validatedData;
    }

    public static function success(array $validatedData = []): self
    {
        return new self(true, [], $validatedData);
    }

    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function merge(self $other): self
    {
        return new self(
            $this->valid && $other->valid,
            array_merge_recursive($this->errors, $other->errors),
            array_merge($this->validatedData, $other->validatedData)
        );
    }
}