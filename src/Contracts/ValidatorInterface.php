<?php

namespace Hydrastat\DtoHydrator\Contracts;

use Hydrastat\DtoHydrator\Core\ValidationResult;

interface ValidatorInterface
{
    /**
     * Validate a DTO object against its validation rules.
     *
     * @param object $dto DTO instance
     *
     * @return ValidationResult
     */
    public function validate(object $dto): ValidationResult;

    /**
     * Validate raw data against DTO class rules (without hydrating).
     *
     * @param string $dtoClass DTO class name
     * @param array  $data     Raw data
     *
     * @return ValidationResult
     */
    public function validateData(string $dtoClass, array $data): ValidationResult;
}