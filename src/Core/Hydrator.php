<?php

namespace Hydrastat\DtoHydrator\Core;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Exceptions\ValidationException;

class Hydrator implements HydratorInterface
{
    public function __construct(
        private readonly MappingProviderInterface $mappingProvider,
        private readonly PropertyAccessor $accessor,
        private readonly TypeCaster $caster,
        private readonly ValidatorInterface $validator,
        private readonly array $defaultOptions = [],
    ) {}

    public function hydrate(object|string $dto, array $data, array $options = []): object
    {
        $options = array_merge($this->defaultOptions, $options);
        $dtoInstance = $this->resolveDtoInstance($dto);
        $mappings = $this->mappingProvider->getMappings($dtoInstance::class);

        foreach ($mappings as $propertyName => $mapping) {
            // readonly: skip during hydration, include during extraction
            if ($mapping->readonly) {
                continue;
            }

            $value = $this->extractValue($data, $mapping, $options);
            
            if ($value === null && $mapping->required && $mapping->default === null) {
                if (!($options['skip_required'] ?? false)) {
                    continue;
                }
            }

            if ($value === null && $mapping->default !== null) {
                $value = $mapping->default instanceof \Closure 
                    ? $mapping->default($dtoInstance) 
                    : $mapping->default;
                
                // Apply caster to default value if specified
                if ($value !== null && $mapping->caster && $this->caster->has($mapping->caster)) {
                    $value = $this->caster->cast($mapping->caster, $value);
                }
            }

            if ($value !== null) {
                $this->accessor->setValue($dtoInstance, $propertyName, $value);
            }
        }

        return $dtoInstance;
    }

    public function extract(object $dto, array $options = []): array
    {
        $options = array_merge($this->defaultOptions, $options);
        $groups = $options['groups'] ?? ['default'];
        $excludeNull = $options['exclude_null'] ?? false;
        $mappings = $this->mappingProvider->getMappings($dto::class);
        $result = [];

        foreach ($mappings as $propertyName => $mapping) {
            // writeonly: skip during extraction, include during hydration
            // readonly: skip during hydration, include during extraction
            if ($mapping->writeonly) {
                continue;
            }

            // Include field if ANY of its groups match the requested groups
            if (empty(array_intersect($mapping->groups, $groups))) {
                continue;
            }

            $value = $this->accessor->getValue($dto, $propertyName);

            if ($excludeNull && $value === null) {
                continue;
            }

            // Format DateTime objects for extraction
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }

            $result[$mapping->source] = $value;
        }

        return $result;
    }

    public function validate(object $dto): \Hydrastat\DtoHydrator\Core\ValidationResult
    {
        return $this->validator->validate($dto);
    }

    public function hydrateAndValidate(object|string $dto, array $data, array $options = []): object
    {
        $dtoInstance = $this->hydrate($dto, $data, $options);
        $result = $this->validate($dtoInstance);

        if (!$result->valid) {
            throw ValidationException::fromResult($result);
        }

        return $dtoInstance;
    }

    private function resolveDtoInstance(object|string $dto): object
    {
        if (is_string($dto)) {
            return new $dto();
        }
        return $dto;
    }

    private function extractValue(array $data, PropertyMapping $mapping, array $options): mixed
    {
        $source = $mapping->source;
        
        if (!array_key_exists($source, $data)) {
            return null;
        }

        $value = $data[$source];

        if ($value === null || $value === '') {
            return null;
        }

        if ($mapping->caster && $this->caster->has($mapping->caster)) {
            $value = $this->caster->cast($mapping->caster, $value);
        }

        return $value;
    }
}