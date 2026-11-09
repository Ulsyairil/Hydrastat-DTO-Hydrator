<?php

namespace Hydrastat\DtoHydrator\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MapFrom
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
}