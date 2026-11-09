<?php

namespace Hydrastat\DtoHydrator\Core;

use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Mapping\Attribute\MapFrom;

class MappingResolver implements MappingProviderInterface
{
    /** @var array<string, PropertyMapping[]> */
    private array $cache = [];

    public function __construct(
        private readonly PropertyAccessor $accessor,
        private readonly array $attributeClasses = [MapFrom::class],
    ) {}

    public function getMappings(string $dtoClass): array
    {
        if (isset($this->cache[$dtoClass])) {
            return $this->cache[$dtoClass];
        }

        $mappings = $this->resolveMappings($dtoClass);
        $this->cache[$dtoClass] = $mappings;

        return $mappings;
    }

    public function hasMappings(string $dtoClass): bool
    {
        return !empty($this->getMappings($dtoClass));
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * @return PropertyMapping[]
     */
    private function resolveMappings(string $dtoClass): array
    {
        if (!class_exists($dtoClass)) {
            return [];
        }

        $reflectionClass = new \ReflectionClass($dtoClass);
        $mappings = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $mapping = $this->resolvePropertyMapping($property);
            if ($mapping !== null) {
                $mappings[$property->getName()] = $mapping;
            }
        }

        return $mappings;
    }

    private function resolvePropertyMapping(\ReflectionProperty $property): ?PropertyMapping
    {
        foreach ($this->attributeClasses as $attributeClass) {
            $attributes = $property->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            
            if (!empty($attributes)) {
                return PropertyMapping::fromAttribute($attributes[0]);
            }
        }

        return null;
    }
}