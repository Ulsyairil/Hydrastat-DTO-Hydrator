<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allow Private Property Access
    |--------------------------------------------------------------------------
    |
    | Whether the hydrator can access private/protected properties via
    | reflection. Enable for DTOs with non-public properties.
    |
    */
    'allow_private_access' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Hydration Options
    |--------------------------------------------------------------------------
    |
    | Default options passed to every hydrate/extract call.
    |
    */
    'default_options' => [
        'groups' => ['default'],
        'exclude_null' => false,
        'skip_required' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validators
    |--------------------------------------------------------------------------
    |
    | Register custom validation rules. Key = rule name, Value = callable.
    | Callable signature: fn(mixed $value, object $dto, array $params): ?string
    | Return null for valid, error message string for invalid.
    |
    */
    'custom_validators' => [
        // 'unique' => fn($value, $dto, $params) => ...
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Casters
    |--------------------------------------------------------------------------
    |
    | Register custom type casters. Key = caster name, Value = callable.
    | Callable signature: fn(mixed $value): mixed
    |
    */
    'custom_casters' => [
        // 'uuid' => fn($v) => (string) $v,
    ],
];