<?php

namespace Hydrastat\DtoHydrator\Core;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyMapping
{
    public function __construct(
        public string $source,
        public ?string $caster = null,
        public bool $required = true,
        public mixed $default = null,
        public array $groups = ['default'],
        public bool $readonly = false,
        public bool $writeonly = false,
        public ?string $validator = null,
    ) {}

    public static function fromAttribute(\ReflectionAttribute $attribute): self
    {
        $args = $attribute->getArguments();
        return new self(
            source: $args['source'] ?? $args[0] ?? '',
            caster: $args['caster'] ?? null,
            required: $args['required'] ?? true,
            default: $args['default'] ?? null,
            groups: $args['groups'] ?? ['default'],
            readonly: $args['readonly'] ?? false,
            writeonly: $args['writeonly'] ?? false,
            validator: $args['validator'] ?? null,
        );
    }
}