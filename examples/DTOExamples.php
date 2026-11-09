<?php

/**
 * Example DTOs demonstrating Hydrastat DTO Hydrator features
 */

namespace App\DTO;

use Hydrastat\DtoHydrator\Mapping\Attribute\MapFrom;
use Hydrastat\DtoHydrator\Mapping\Attribute\Validate;

// ============================================================
// Basic User DTO
// ============================================================
class UserCreateDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:2')]
    #[Validate('max:100')]
    public string $name = '';

    #[MapFrom('email', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email = '';

    #[MapFrom('password', caster: 'string', writeonly: true)]
    #[Validate('required')]
    #[Validate('min:8')]
    #[Validate('confirmed')] // expects password_confirmation in data
    public string $password = '';

    #[MapFrom('age', caster: 'int', required: false, default: 18)]
    #[Validate('integer')]
    #[Validate('min:13')]
    #[Validate('max:120')]
    public int $age = 18;

    #[MapFrom('is_active', caster: 'bool', required: false, default: true)]
    public bool $isActive = true;

    #[MapFrom('tags', caster: 'array', required: false, default: [])]
    #[Validate('in:admin,user,premium,guest', parameters: [['admin','user','premium','guest']])]
    public array $tags = [];

    #[MapFrom('created_at', caster: 'datetime', readonly: true)]
    public \DateTimeImmutable $createdAt;
}

// ============================================================
// DTO with nested objects (manual handling)
// ============================================================
class AddressDTO
{
    #[MapFrom('street', caster: 'trim')]
    #[Validate('required')]
    public string $street = '';

    #[MapFrom('city', caster: 'trim')]
    #[Validate('required')]
    public string $city = '';

    #[MapFrom('postal_code', caster: 'trim')]
    #[Validate('required')]
    #[Validate('regex:/^\d{5}$/')]
    public string $postalCode = '';

    #[MapFrom('country', caster: 'upper', required: false, default: 'US')]
    #[Validate('in:US,CA,UK,AU,DE,FR,JP')]
    public string $country = 'US';
}

class UserWithAddressDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';

    #[MapFrom('email', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email = '';

    // Nested DTO - handled manually after hydration
    public ?AddressDTO $address = null;
}

// ============================================================
// API Response DTO (extraction only, with groups)
// ============================================================
class UserResponseDTO
{
    #[MapFrom('id', caster: 'int', readonly: true, groups: ['public', 'admin'])]
    public int $id = 0;

    #[MapFrom('name', caster: 'trim', groups: ['public', 'admin'])]
    public string $name = '';

    #[MapFrom('email', caster: 'email', groups: ['public', 'admin'])]
    public string $email = '';

    #[MapFrom('role', groups: ['admin'])]
    public string $role = 'user';

    #[MapFrom('last_login_at', caster: 'datetime', readonly: true, groups: ['admin'])]
    public ?\DateTimeImmutable $lastLoginAt = null;

    #[MapFrom('created_at', caster: 'datetime', readonly: true, groups: ['public', 'admin'])]
    public \DateTimeImmutable $createdAt;
}

// ============================================================
// Form DTO with custom validation
// ============================================================
class ContactFormDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:2')]
    #[Validate('max:100')]
    public string $name = '';

    #[MapFrom('email', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email = '';

    #[MapFrom('subject', caster: 'trim')]
    #[Validate('required')]
    #[Validate('in:support,sales,billing,other')]
    public string $subject = 'support';

    #[MapFrom('message', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:10')]
    #[Validate('max:5000')]
    public string $message = '';

    #[MapFrom('newsletter', caster: 'bool', required: false, default: false)]
    public bool $newsletter = false;
}

// ============================================================
// Usage Examples
// ============================================================

/*
// Laravel Controller
use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use App\DTO\UserCreateDTO;

class UserController extends Controller
{
    public function __construct(private HydratorInterface $hydrator) {}

    public function store(Request $request)
    {
        // Hydrate and validate in one call
        $dto = $this->hydrator->hydrateAndValidate(
            UserCreateDTO::class,
            $request->validated()
        );

        // DTO is fully typed and validated
        $user = $this->userService->create($dto);

        // Extract for response (exclude writeonly fields like password)
        return response()->json($this->hydrator->extract($user, [
            'groups' => ['public'],
            'exclude_null' => true,
        ]));
    }
}

// Standalone Usage
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

$data = [
    'name' => '  John Doe  ',
    'email' => 'JOHN@EXAMPLE.COM',
    'password' => 'secret123',
    'password_confirmation' => 'secret123',
    'age' => '25',
];

$dto = $hydrator->hydrateAndValidate(UserCreateDTO::class, $data);

// $dto->name === 'John Doe'
// $dto->email === 'john@example.com'
// $dto->age === 25
// $dto->password === 'secret123'

// Extract for API response
$response = $hydrator->extract($dto, [
    'groups' => ['default'],
    'exclude_null' => true,
]);
// $response = [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'age' => 25,
//     'is_active' => true,
//     'tags' => [],
//     'created_at' => '2024-01-15T10:30:00+00:00',
// ]
// Note: password is excluded (writeonly: true)
*/

/*
// Custom Casters & Validators
$caster = new TypeCaster();
$caster->register('uuid', fn($v) => (string) $v);
$caster->register('carbon', fn($v) => \Carbon\Carbon::parse($v));

$validator = new Validator();
$validator->register('unique_email', function($value, $dto, $params) {
    [$table, $column] = $params;
    $exists = DB::table($table)->where($column, $value)->exists();
    return $exists ? 'Email already registered' : null;
});
$validator->register('strong_password', function($value) {
    if (strlen($value) < 12) return 'Password must be at least 12 characters';
    if (!preg_match('/[A-Z]/', $value)) return 'Password must contain uppercase';
    if (!preg_match('/[a-z]/', $value)) return 'Password must contain lowercase';
    if (!preg_match('/\d/', $value)) return 'Password must contain number';
    if (!preg_match('/[^A-Za-z0-9]/', $value)) return 'Password must contain special character';
    return null;
});
*/