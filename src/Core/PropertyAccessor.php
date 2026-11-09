<?php

namespace Hydrastat\DtoHydrator\Core;

class PropertyAccessor
{
    public function __construct(
        private readonly bool $allowPrivateAccess = true,
    ) {}

    public function getValue(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object::class, $property);
        
        if (!$reflection->isPublic()) {
            $reflection->setAccessible($this->allowPrivateAccess);
        }
        
        return $reflection->getValue($object);
    }

    public function setValue(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object::class, $property);
        
        if (!$reflection->isPublic()) {
            $reflection->setAccessible($this->allowPrivateAccess);
        }
        
        if ($reflection->isReadOnly() && $reflection->isInitialized($object)) {
            throw new \LogicException("Cannot set readonly property '{$property}' after initialization");
        }
        
        $reflection->setValue($object, $value);
    }

    public function hasProperty(object|string $class, string $property): bool
    {
        $reflectionClass = new \ReflectionClass($class);
        return $reflectionClass->hasProperty($property);
    }

    public function getPropertyType(object|string $class, string $property): ?\ReflectionNamedType
    {
        $reflection = new \ReflectionProperty($class, $property);
        $type = $reflection->getType();
        return $type instanceof \ReflectionNamedType ? $type : null;
    }

    public function isReadOnly(object|string $class, string $property): bool
    {
        $reflection = new \ReflectionProperty($class, $property);
        return $reflection->isReadOnly();
    }

    public function isStatic(object|string $class, string $property): bool
    {
        $reflection = new \ReflectionProperty($class, $property);
        return $reflection->isStatic();
    }
}