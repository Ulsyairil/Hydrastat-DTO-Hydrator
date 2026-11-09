<?php

namespace Hydrastat\DtoHydrator\Contracts;

interface MappingProviderInterface
{
    /**
     * Get property mapping metadata for a DTO class.
     *
     * @param string $dtoClass Fully qualified DTO class name
     *
     * @return PropertyMapping[] Array of property mappings
     */
    public function getMappings(string $dtoClass): array;

    /**
     * Check if mappings exist for a class.
     */
    public function hasMappings(string $dtoClass): bool;

    /**
     * Clear cached mappings (useful for testing/hot reload).
     */
    public function clearCache(): void;
}