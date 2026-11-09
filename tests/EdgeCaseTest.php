<?php

namespace Hydrastat\DtoHydrator\Tests;

use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Hydrastat\DtoHydrator\Mapping\Attribute\MapFrom;
use Hydrastat\DtoHydrator\Mapping\Attribute\Validate;
use PHPUnit\Framework\TestCase;

// ============================================================
// Test DTOs (defined at top level to avoid nested class issues)
// ============================================================

class NestedDTO
{
    #[MapFrom('street', caster: 'trim')]
    #[Validate('required')]
    public string $street = '';

    #[MapFrom('city', caster: 'trim')]
    #[Validate('required')]
    public string $city = '';
}

class ParentDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';

    // Not auto-mapped - handled manually
    public ?NestedDTO $address = null;
}

class ReadonlyDTO
{
    #[MapFrom('id', caster: 'int', readonly: true)]
    public int $id = 0;

    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';

    #[MapFrom('created_at', caster: 'datetime', readonly: true)]
    public \DateTimeImmutable $createdAt;
}

class WriteonlyDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';

    #[MapFrom('password', caster: 'string', writeonly: true)]
    #[Validate('required')]
    #[Validate('min:8')]
    public string $password = '';

    #[MapFrom('api_key', caster: 'string', writeonly: true)]
    public string $apiKey = '';
}

class GroupedDTO
{
    #[MapFrom('id', caster: 'int', groups: ['public', 'admin'])]
    public int $id = 0;

    #[MapFrom('name', caster: 'trim', groups: ['public'])]
    public string $name = '';

    #[MapFrom('email', caster: 'email', groups: ['public', 'admin'])]
    public string $email = '';

    #[MapFrom('role', groups: ['admin'])]
    public string $role = 'user';

    #[MapFrom('internal_notes', groups: ['internal'])]
    public string $internalNotes = '';
}

class DefaultClosureDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';

    #[MapFrom('created_at', caster: 'datetime', required: false, default: '2024-01-01')]
    public \DateTimeImmutable $createdAt;

    #[MapFrom('tags', caster: 'array', required: false, default: ['user', 'auto'])]
    public array $tags = [];
}

class CaseInsensitiveDTO
{
    #[MapFrom('userName', caster: 'trim')]
    #[Validate('required')]
    public string $userName = '';

    #[MapFrom('EMAIL', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $EMAIL = '';
}

class EmptyArrayDTO
{
    #[MapFrom('tags', caster: 'array', required: false, default: [])]
    public array $tags = [];

    #[MapFrom('metadata', caster: 'json', required: false, default: '{}')]
    public array $metadata = [];
}

class BoolTestDTO
{
    #[MapFrom('active', caster: 'bool', required: false, default: false)]
    public bool $active = false;
}

class FloatTestDTO
{
    #[MapFrom('price', caster: 'float', required: false, default: 0.0)]
    public float $price = 0.0;
}

class DateTimeTestDTO
{
    #[MapFrom('dt', caster: 'datetime', required: false)]
    public ?\DateTimeImmutable $dt = null;
}

class RequiredDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';
    
    #[MapFrom('email', caster: 'email')]
    #[Validate('required')]
    public string $email = '';
}

class OptionalDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    public string $name = '';
    
    #[MapFrom('optional_field', caster: 'string', required: false, default: 'default')]
    public string $optionalField = 'default';
}

class TypedDTO
{
    #[MapFrom('count')]
    public int $count = 0;
    
    #[MapFrom('ratio')]
    public float $ratio = 0.0;
    
    #[MapFrom('active')]
    public bool $active = false;
    
    #[MapFrom('name')]
    public string $name = '';
}

class NumericDTO
{
    #[MapFrom('age', caster: 'int')]
    #[Validate('min:18')]
    #[Validate('max:100')]
    public int $age = 0;
    
    #[MapFrom('score', caster: 'float')]
    #[Validate('min:0')]
    #[Validate('max:100')]
    public float $score = 0.0;
}

class BetweenDTO
{
    #[MapFrom('rating', caster: 'int')]
    #[Validate('between:1,5')]
    public int $rating = 0;
}

class InDTO
{
    #[MapFrom('status', caster: 'string')]
    #[Validate('in:draft,published,archived')]
    public string $status = '';
}

class RegexDTO
{
    #[MapFrom('username', caster: 'string')]
    #[Validate('regex:/^[a-zA-Z0-9_]+$/')]
    public string $username = '';
}

class ConfirmedDTO
{
    #[MapFrom('password', caster: 'string', writeonly: true)]
    #[Validate('required')]
    #[Validate('confirmed')]
    public string $password = '';

    #[MapFrom('password_confirmation', caster: 'string', writeonly: true)]
    public string $password_confirmation = '';
}

class DefaultDTO
{
    #[MapFrom('name', caster: 'trim')]
    public string $name = '';
    
    #[MapFrom('age', caster: 'int', required: false, default: 25)]
    public int $age = 25;
    
    #[MapFrom('nickname', required: false, default: null)]
    public ?string $nickname = null;
}

class StrictDTO
{
    #[MapFrom('name', caster: 'trim')]
    public string $name = '';
}

class StaticDTO
{
    #[MapFrom('name', caster: 'trim')]
    public string $name = '';
    
    public static string $staticField = 'static';
}

// ============================================================
// Tests
// ============================================================

class EdgeCaseTest extends TestCase
{
    private Hydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new Hydrator(
            new MappingResolver(new PropertyAccessor()),
            new PropertyAccessor(),
            new TypeCaster(),
            new Validator()
        );
    }

    public function testHydrateNestedDTOManual(): void
    {
        $data = [
            'name' => 'John',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Boston',
            ],
        ];

        $dto = $this->hydrator->hydrate(ParentDTO::class, $data);

        $this->assertEquals('John', $dto->name);
        $this->assertNull($dto->address); // Not auto-mapped

        // Manual nested hydration
        $addressDto = $this->hydrator->hydrate(NestedDTO::class, $data['address']);
        $dto->address = $addressDto;

        $this->assertEquals('123 Main St', $dto->address->street);
        $this->assertEquals('Boston', $dto->address->city);
    }

    public function testReadonlyFieldsSkippedDuringHydration(): void
    {
        $data = [
            'id' => '999',
            'name' => 'Test',
            'created_at' => '2020-01-01',
        ];

        $dto = $this->hydrator->hydrate(ReadonlyDTO::class, $data);

        // readonly fields should be skipped during hydration
        $this->assertEquals(0, $dto->id); // default value
        $this->assertEquals('Test', $dto->name);
        // createdAt not set (readonly)
    }

    public function testReadonlyFieldsIncludedInExtraction(): void
    {
        $dto = new ReadonlyDTO();
        $dto->id = 42;
        $dto->name = 'Test User';
        $dto->createdAt = new \DateTimeImmutable('2024-01-15T10:30:00Z');

        $extracted = $this->hydrator->extract($dto);

        $this->assertEquals(42, $extracted['id']);
        $this->assertEquals('Test User', $extracted['name']);
        $this->assertEquals('2024-01-15T10:30:00+00:00', $extracted['created_at']);
    }

    public function testWriteonlyFieldsHydratedButNotExtracted(): void
    {
        $data = [
            'name' => 'John',
            'password' => 'secret123',
            'api_key' => 'key-123',
        ];

        $dto = $this->hydrator->hydrate(WriteonlyDTO::class, $data);

        $this->assertEquals('John', $dto->name);
        $this->assertEquals('secret123', $dto->password);
        $this->assertEquals('key-123', $dto->apiKey);

        $extracted = $this->hydrator->extract($dto);

        $this->assertEquals('John', $extracted['name']);
        $this->assertArrayNotHasKey('password', $extracted);
        $this->assertArrayNotHasKey('api_key', $extracted);
    }

    public function testExtractionGroups(): void
    {
        $dto = new GroupedDTO();
        $dto->id = 1;
        $dto->name = 'John';
        $dto->email = 'john@test.com';
        $dto->role = 'admin';
        $dto->internalNotes = 'secret';

        // Public group
        $public = $this->hydrator->extract($dto, ['groups' => ['public']]);
        $this->assertEquals(1, $public['id']);
        $this->assertEquals('John', $public['name']);
        $this->assertEquals('john@test.com', $public['email']);
        $this->assertArrayNotHasKey('role', $public);
        $this->assertArrayNotHasKey('internal_notes', $public);

        // Admin group
        $admin = $this->hydrator->extract($dto, ['groups' => ['admin']]);
        $this->assertEquals(1, $admin['id']);
        $this->assertEquals('john@test.com', $admin['email']);
        $this->assertEquals('admin', $admin['role']);
        $this->assertArrayNotHasKey('name', $admin);
        $this->assertArrayNotHasKey('internal_notes', $admin);

        // Internal group
        $internal = $this->hydrator->extract($dto, ['groups' => ['internal']]);
        $this->assertEquals('secret', $internal['internal_notes']);
        $this->assertArrayNotHasKey('id', $internal);
    }

    public function testMultipleGroups(): void
    {
        $dto = new GroupedDTO();
        $dto->id = 1;
        $dto->name = 'John';
        $dto->email = 'john@test.com';

        $extracted = $this->hydrator->extract($dto, ['groups' => ['public', 'admin']]);
        
        $this->assertEquals(1, $extracted['id']);
        $this->assertEquals('John', $extracted['name']);
        $this->assertEquals('john@test.com', $extracted['email']);
    }

    public function testDefaultStaticValues(): void
    {
        $data = ['name' => 'Test'];
        
        $dto = $this->hydrator->hydrate(DefaultClosureDTO::class, $data);

        $this->assertEquals('Test', $dto->name);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->createdAt);
        $this->assertEquals(['user', 'auto'], $dto->tags);
    }

    public function testCaseSensitiveSourceKeys(): void
    {
        $data = [
            'userName' => '  john  ',
            'EMAIL' => 'JOHN@EXAMPLE.COM',
        ];

        $dto = $this->hydrator->hydrate(CaseInsensitiveDTO::class, $data);

        $this->assertEquals('john', $dto->userName);
        $this->assertEquals('john@example.com', $dto->EMAIL);
    }

    public function testEmptyArrayDefault(): void
    {
        $data = ['name' => 'Test'];
        
        $dto = $this->hydrator->hydrate(EmptyArrayDTO::class, $data);

        $this->assertEquals([], $dto->tags);
        $this->assertEquals([], $dto->metadata);
    }

    public function testEmptyArrayFromInput(): void
    {
        $data = [
            'name' => 'Test',
            'tags' => [],
            'metadata' => '{}',
        ];
        
        $dto = $this->hydrator->hydrate(EmptyArrayDTO::class, $data);

        $this->assertEquals([], $dto->tags);
        $this->assertEquals([], $dto->metadata);
    }

    public function testJsonCaster(): void
    {
        $data = [
            'name' => 'Test',
            'metadata' => '{"key": "value", "count": 42}',
        ];
        
        $dto = $this->hydrator->hydrate(EmptyArrayDTO::class, $data);

        $this->assertEquals(['key' => 'value', 'count' => 42], $dto->metadata);
    }

    public function testJsonCasterInvalidJson(): void
    {
        $data = [
            'name' => 'Test',
            'metadata' => 'not valid json',
        ];
        
        $dto = $this->hydrator->hydrate(EmptyArrayDTO::class, $data);

        // Invalid JSON falls back to default {} -> []
        $this->assertEquals([], $dto->metadata);
    }

    public function testBooleanCasterVariations(): void
    {
        $trueCases = ['true', '1', 1, true, 'yes'];
        foreach ($trueCases as $val) {
            $dto = $this->hydrator->hydrate(BoolTestDTO::class, ['active' => $val]);
            $this->assertTrue($dto->active, "Expected true for input: " . var_export($val, true));
        }

        $falseCases = ['false', '0', 0, false, 'no'];
        foreach ($falseCases as $val) {
            $dto = $this->hydrator->hydrate(BoolTestDTO::class, ['active' => $val]);
            $this->assertFalse($dto->active, "Expected false for input: " . var_export($val, true));
        }
    }

    public function testFloatCaster(): void
    {
        $testCases = [
            '10.99' => 10.99,
            '0' => 0.0,
            '-5.5' => -5.5,
            42 => 42.0,
        ];

        foreach ($testCases as $input => $expected) {
            $dto = $this->hydrator->hydrate(FloatTestDTO::class, ['price' => $input]);
            $this->assertEquals($expected, $dto->price, "Failed for input: $input");
        }
    }

    public function testDateTimeCasterFormats(): void
    {
        $testCases = [
            '2024-01-15' => '2024-01-15T00:00:00+00:00',
            '2024-01-15 10:30:00' => '2024-01-15T10:30:00+00:00',
            '2024-01-15T10:30:00Z' => '2024-01-15T10:30:00+00:00',
        ];

        foreach ($testCases as $input => $expected) {
            $dto = $this->hydrator->hydrate(DateTimeTestDTO::class, ['dt' => $input]);
            $this->assertNotNull($dto->dt);
            $this->assertEquals($expected, $dto->dt->format(\DateTimeInterface::ATOM));
        }
    }

    public function testSkipRequiredOption(): void
    {
        $data = ['name' => 'John']; // missing email

        // Without skip_required - email will be empty string (default)
        $dto = $this->hydrator->hydrate(RequiredDTO::class, $data);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('', $dto->email); // required but missing, gets default

        // With skip_required - truly skips
        $dto = $this->hydrator->hydrate(RequiredDTO::class, $data, ['skip_required' => true]);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('', $dto->email);
    }

    public function testExtractExcludeNullWithDefaults(): void
    {
        $dto = new DefaultDTO();
        $dto->name = 'John';
        // age = 25 (default), nickname = null

        $extracted = $this->hydrator->extract($dto, ['exclude_null' => true]);

        $this->assertEquals('John', $extracted['name']);
        $this->assertEquals(25, $extracted['age']); // default not null
        $this->assertArrayNotHasKey('nickname', $extracted); // null excluded
    }

    public function testValidatorMinMaxNumeric(): void
    {
        // Valid
        $dto = $this->hydrator->hydrateAndValidate(NumericDTO::class, ['age' => '25', 'score' => '85.5']);
        $this->assertEquals(25, $dto->age);
        $this->assertEquals(85.5, $dto->score);

        // Invalid min
        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        $this->hydrator->hydrateAndValidate(NumericDTO::class, ['age' => '15', 'score' => '85.5']);
    }

    public function testValidatorBetween(): void
    {
        $dto = $this->hydrator->hydrateAndValidate(BetweenDTO::class, ['rating' => '3']);
        $this->assertEquals(3, $dto->rating);

        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        $this->hydrator->hydrateAndValidate(BetweenDTO::class, ['rating' => '6']);
    }

    public function testValidatorIn(): void
    {
        $dto = $this->hydrator->hydrateAndValidate(InDTO::class, ['status' => 'published']);
        $this->assertEquals('published', $dto->status);

        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        $this->hydrator->hydrateAndValidate(InDTO::class, ['status' => 'invalid']);
    }

    public function testValidatorRegex(): void
    {
        $dto = $this->hydrator->hydrateAndValidate(RegexDTO::class, ['username' => 'john_doe123']);
        $this->assertEquals('john_doe123', $dto->username);

        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        $this->hydrator->hydrateAndValidate(RegexDTO::class, ['username' => 'john doe!']);
    }

    public function testValidatorConfirmed(): void
    {
        $dto = $this->hydrator->hydrateAndValidate(ConfirmedDTO::class, [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $this->assertEquals('secret123', $dto->password);

        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        $this->hydrator->hydrateAndValidate(ConfirmedDTO::class, [
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);
    }

    public function testPropertyTypeInference(): void
    {
        $data = [
            'count' => '42',
            'ratio' => '3.14',
            'active' => 'true',
            'name' => 'Test',
        ];

        $dto = $this->hydrator->hydrate(TypedDTO::class, $data);

        $this->assertIsInt($dto->count);
        $this->assertEquals(42, $dto->count);
        
        $this->assertIsFloat($dto->ratio);
        $this->assertEquals(3.14, $dto->ratio);
        
        $this->assertIsBool($dto->active);
        $this->assertTrue($dto->active);
        
        $this->assertIsString($dto->name);
        $this->assertEquals('Test', $dto->name);
    }

    public function testMissingPropertyInData(): void
    {
        $dto = $this->hydrator->hydrate(OptionalDTO::class, ['name' => 'Test']);

        $this->assertEquals('Test', $dto->name);
        $this->assertEquals('default', $dto->optionalField);
    }

    public function testExtraPropertyInDataIgnored(): void
    {
        $data = [
            'name' => 'Test',
            'extra_field' => 'ignored',
            'another_extra' => 123,
        ];

        $dto = $this->hydrator->hydrate(StrictDTO::class, $data);

        $this->assertEquals('Test', $dto->name);
        // Extra fields silently ignored
    }

    public function testStaticPropertiesIgnored(): void
    {
        $data = ['name' => 'Test', 'staticField' => 'changed'];
        
        $dto = $this->hydrator->hydrate(StaticDTO::class, $data);

        $this->assertEquals('Test', $dto->name);
        $this->assertEquals('static', StaticDTO::$staticField); // unchanged
    }
}