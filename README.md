# Hydrastat DTO Hydrator

[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777bb4?logo=php&logoColor=white)](composer.json)
[![Packagist Version](https://img.shields.io/packagist/v/hydrastat/dto-hydrator.svg)](https://packagist.org/packages/hydrastat/dto-hydrator)
[![Packagist Downloads](https://img.shields.io/packagist/dt/hydrastat/dto-hydrator.svg)](https://packagist.org/packages/hydrastat/dto-hydrator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-36%20passing-brightgreen.svg)](#test)
[![External calls](https://img.shields.io/badge/external%20calls-zero-success.svg)](#why-local)

Framework-agnostic DTO hydrator with Laravel, CodeIgniter, and Symfony integrations.

## Installation

```bash
composer require hydrastat/dto-hydrator
```

## Quick Start

### 1. Define your DTO with attributes

```php
<?php

namespace App\DTO;

use Hydrastat\DtoHydrator\Mapping\Attribute\MapFrom;
use Hydrastat\DtoHydrator\Mapping\Attribute\Validate;

class UserCreateDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:2')]
    #[Validate('max:100')]
    public string $name;

    #[MapFrom('email_address', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email;

    #[MapFrom('password', caster: 'string', writeonly: true)]
    #[Validate('required')]
    #[Validate('min:8')]
    public string $password;

    #[MapFrom('age', caster: 'int', required: false, default: 18)]
    #[Validate('integer')]
    #[Validate('min:13')]
    public int $age = 18;

    #[MapFrom('is_active', caster: 'bool', required: false, default: true)]
    public bool $isActive = true;

    #[MapFrom('created_at', caster: 'datetime', readonly: true)]
    public \DateTimeImmutable $createdAt;
}
```

### 2. Use in your framework

#### Laravel

```php
// config/app.php - auto-discovered via service provider

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;

class UserController
{
    public function __construct(private HydratorInterface $hydrator) {}

    public function store(Request $request)
    {
        $dto = $this->hydrator->hydrateAndValidate(
            UserCreateDTO::class, 
            $request->validated()
        );

        $user = $this->userService->create($dto);

        return response()->json($this->hydrator->extract($user));
    }
}

// Or via Facade
use Hydrastat\DtoHydrator\Integrations\Laravel\Facade as Hydrator;

$dto = Hydrator::hydrateAndValidate(UserCreateDTO::class, $data);
```

#### CodeIgniter 4

```php
// app/Config/Services.php - auto-registered

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;

class UserController extends BaseController
{
    private HydratorInterface $hydrator;

    public function __construct()
    {
        $this->hydrator = service('hydrator');
    }

    public function create()
    {
        $dto = $this->hydrator->hydrateAndValidate(
            UserCreateDTO::class,
            $this->request->getJSON(true)
        );

        // ...
    }
}
```

#### Symfony

```php
// config/bundles.php - auto-registered
// Hydrastat\DtoHydrator\Integrations\Symfony\Bundle::class => ['all' => true],

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;

class UserController
{
    public function __construct(private HydratorInterface $hydrator) {}

    #[Route('/api/users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $dto = $this->hydrator->hydrateAndValidate(UserCreateDTO::class, $data);
        
        $user = $this->userService->create($dto);

        return $this->json($this->hydrator->extract($user));
    }
}
```

#### Framework-agnostic (standalone)

```php
use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;

$hydrator = new Hydrator(
    new MappingResolver(new PropertyAccessor()),
    new PropertyAccessor(),
    new TypeCaster(),
    new Validator()
);

$dto = $hydrator->hydrateAndValidate(UserCreateDTO::class, $_POST);
```

## Mapping Attributes

### `#[MapFrom]`

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `source` | string | - | Source key in input data |
| `caster` | string|null | null | Type caster name (int, float, bool, trim, email, datetime, etc.) |
| `required` | bool | true | Whether field is required |
| `default` | mixed | null | Default value if missing (supports Closure) |
| `groups` | array | ['default'] | Extraction groups |
| `readonly` | bool | false | Skip during hydration, include in extraction |
| `writeonly` | bool | true | Include in hydration, skip during extraction |
| `validator` | string|null | null | Custom validator reference |

### `#[Validate]`

```php
#[Validate('required')]
#[Validate('email')]
#[Validate('min:2')]
#[Validate('max:100')]
#[Validate('between:1,100')]
#[Validate('in:admin,user,guest')]
#[Validate('regex:/^[a-z]+$/')]
#[Validate('confirmed')] // expects {field}_confirmation
public string $field;
```

### Built-in Casters

```
int, integer, float, double, bool, boolean, string, trim, 
lower, upper, email, array, json, datetime, date, timestamp
```

### Built-in Validators

```
required, email, min, max, between, numeric, integer, boolean,
date, in, regex, unique, confirmed
```

## Custom Casters & Validators

```php
// Laravel: config/hydrator.php
'custom_casters' => [
    'uuid' => fn($v) => (string) $v,
    'carbon' => fn($v) => \Carbon\Carbon::parse($v),
],

'custom_validators' => [
    'unique' => function($value, $dto, $params) {
        [$table, $column] = $params;
        $exists = DB::table($table)->where($column, $value)->exists();
        return $exists ? 'Already exists' : null;
    },
],
```

## Extraction Groups

```php
// Only extract 'api' group
$hydrator->extract($dto, ['groups' => ['api']]);

// Exclude null values
$hydrator->extract($dto, ['exclude_null' => true]);
```

## Testing

```php
use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;

$hydrator = new Hydrator(
    new MappingResolver(new PropertyAccessor()),
    new PropertyAccessor(),
    new TypeCaster(),
    new Validator()
);

$dto = $hydrator->hydrate(UserCreateDTO::class, [
    'name' => '  John Doe  ',
    'email_address' => 'JOHN@EXAMPLE.COM',
    'password' => 'secret123',
]);

assert($dto->name === 'John Doe');
assert($dto->email === 'john@example.com');
assert($dto->age === 18); // default
assert($dto->isActive === true); // default
```

## License

MIT

---

## Why Local?

This library has **zero external HTTP calls** — no telemetry, no license checks, no update pings. Everything runs in-process. The "external calls: zero" badge reflects this design principle.