<?php

namespace Hydrastat\DtoHydrator\Integrations\Symfony;

use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Core\ValidationResult;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

class ValidatorIntegration implements ValidatorInterface
{
    public function __construct(
        private readonly SymfonyValidatorInterface $symfonyValidator,
    ) {}

    public function validate(object $dto): ValidationResult
    {
        $violations = $this->symfonyValidator->validate($dto);
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return empty($errors)
            ? ValidationResult::success()
            : ValidationResult::failure($errors);
    }

    public function validateData(string $dtoClass, array $data): ValidationResult
    {
        $dto = new $dtoClass();
        
        foreach ($data as $property => $value) {
            if (property_exists($dto, $property)) {
                $dto->$property = $value;
            }
        }

        return $this->validate($dto);
    }
}