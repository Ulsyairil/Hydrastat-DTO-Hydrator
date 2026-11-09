<?php

namespace Hydrastat\DtoHydrator\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validate
{
    public function __construct(
        public string $rule,
        public array $parameters = [],
        public ?string $message = null,
    ) {}
}