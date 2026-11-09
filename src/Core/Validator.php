<?php

namespace Hydrastat\DtoHydrator\Core;

use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Mapping\Attribute\Validate;

class Validator implements ValidatorInterface
{
    /** @var array<string, callable(mixed, object): ?string> */
    private array $validators = [];

    public function __construct()
    {
        $this->registerBuiltInValidators();
    }

    public function register(string $name, callable $validator): void
    {
        $this->validators[$name] = $validator;
    }

    public function validate(object $dto): ValidationResult
    {
        $reflectionClass = new \ReflectionClass($dto);
        $errors = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertyErrors = $this->validateProperty($dto, $property);
            if (!empty($propertyErrors)) {
                $errors[$property->getName()] = $propertyErrors;
            }
        }

        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    public function validateData(string $dtoClass, array $data): ValidationResult
    {
        $dto = new $dtoClass();
        $hydrator = new Hydrator(
            new MappingResolver(new PropertyAccessor()),
            new PropertyAccessor(),
            new TypeCaster(),
            $this
        );
        
        $hydrated = $hydrator->hydrate($dto, $data, ['skip_required' => true]);
        return $this->validate($hydrated);
    }

    private function validateProperty(object $dto, \ReflectionProperty $property): array
    {
        $errors = [];
        $value = $property->isPublic() || $property->isInitialized($dto)
            ? $property->getValue($dto)
            : null;

        $attributes = $property->getAttributes(Validate::class, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $validateAttr = $attribute->newInstance();
            $error = $this->runValidator($validateAttr->rule, $value, $validateAttr->parameters ?? [], $dto, $property->getName());
            
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function runValidator(string $rule, mixed $value, array $params, object $dto, string $propertyName = ''): ?string
    {
        // Special handling for 'in' rule with comma-separated values
        if (str_starts_with($rule, 'in:')) {
            $parts = explode(':', $rule, 2);
            $rule = 'in';
            $params = array_merge(explode(',', $parts[1]), $params);
        }
        
        // Special handling for 'between' rule with comma-separated values
        if (str_starts_with($rule, 'between:')) {
            $parts = explode(':', $rule, 2);
            $rule = 'between';
            $params = array_merge(explode(',', $parts[1]), $params);
        }

        if (isset($this->validators[$rule])) {
            return ($this->validators[$rule])($value, $dto, $params, $propertyName);
        }

        if (str_contains($rule, ':')) {
            $parts = explode(':', $rule);
            $rule = array_shift($parts);
            $params = array_merge($parts, $params);
            
            if (isset($this->validators[$rule])) {
                return ($this->validators[$rule])($value, $dto, $params, $propertyName);
            }
        }

        return null;
    }

    private function registerBuiltInValidators(): void
    {
        $this->validators = [
            'required' => fn($v) => $v === null || $v === '' ? 'This field is required' : null,
            'email' => fn($v) => is_string($v) && filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email format',
            'min' => function($v, $d, $p) {
                $min = $p[0] ?? 0;
                if (is_numeric($v)) {
                    return (float)$v >= (float)$min ? null : "Minimum value is {$min}";
                }
                return (strlen((string)$v) ?? 0) >= $min ? null : "Minimum length is {$min}";
            },
            'max' => function($v, $d, $p) {
                $max = $p[0] ?? PHP_INT_MAX;
                if (is_numeric($v)) {
                    return (float)$v <= (float)$max ? null : "Maximum value is {$max}";
                }
                return (strlen((string)$v) ?? 0) <= $max ? null : "Maximum length is {$max}";
            },
            'between' => function($v, $d, $p) {
                $min = (float)($p[0] ?? 0);
                $max = (float)($p[1] ?? PHP_INT_MAX);
                if (is_numeric($v)) {
                    $val = (float)$v;
                    return $val >= $min && $val <= $max ? null : "Value must be between {$min} and {$max}";
                }
                $len = strlen((string)$v) ?? 0;
                return $len >= $min && $len <= $max ? null : "Length must be between {$min} and {$max}";
            },
            'numeric' => fn($v) => is_numeric($v) ? null : 'Must be numeric',
            'integer' => fn($v) => filter_var($v, FILTER_VALIDATE_INT) !== false ? null : 'Must be an integer',
            'boolean' => fn($v) => is_bool($v) || in_array($v, [0, 1, '0', '1', 'true', 'false'], true) ? null : 'Must be boolean',
            'date' => fn($v) => $v instanceof \DateTimeInterface || (is_string($v) && strtotime($v) !== false) ? null : 'Invalid date',
            'in' => fn($v, $d, $p) => in_array($v, $p, true) ? null : 'Invalid value',
            'regex' => fn($v, $d, $p) => is_string($v) && preg_match($p[0] ?? '', $v) ? null : 'Invalid format',
            'unique' => fn($v, $d, $p) => null, // Placeholder - requires DB connection
            'confirmed' => function($v, $d, $p, $propertyName = '') {
                $field = $p[0] ?? ($propertyName ? $propertyName . '_confirmation' : null);
                if (!$field) {
                    return 'Confirmation field not found';
                }
                $reflection = new \ReflectionClass($d);
                if ($reflection->hasProperty($field)) {
                    $prop = $reflection->getProperty($field);
                    $prop->setAccessible(true);
                    return $prop->getValue($d) === $v ? null : 'Confirmation does not match';
                }
                return 'Confirmation field not found';
            },
        ];
    }
}