<?php

namespace Hydrastat\DtoHydrator\Contracts;

use Hydrastat\DtoHydrator\Core\ValidationResult;

interface HydratorInterface
{
    /**
     * Hydrate a DTO object from array data.
     *
     * @param object|string $dto   DTO instance or class name
     * @param array         $data  Source data (request, JSON, etc.)
     * @param array         $options Hydration options
     *
     * @return object Hydrated DTO instance
     */
    public function hydrate(object|string $dto, array $data, array $options = []): object;

    /**
     * Extract data from a DTO object to array.
     *
     * @param object $dto     DTO instance
     * @param array  $options Extraction options (e.g., 'groups', 'exclude_null')
     *
     * @return array Extracted data
     */
    public function extract(object $dto, array $options = []): array;

    /**
     * Validate a DTO object.
     *
     * @param object $dto DTO instance
     *
     * @return ValidationResult Validation result with errors if any
     */
    public function validate(object $dto): ValidationResult;

    /**
     * Hydrate and validate in one call.
     *
     * @param object|string $dto   DTO instance or class name
     * @param array         $data  Source data
     * @param array         $options Hydration options
     *
     * @return object Hydrated DTO instance
     *
     * @throws ValidationException If validation fails
     */
    public function hydrateAndValidate(object|string $dto, array $data, array $options = []): object;
}