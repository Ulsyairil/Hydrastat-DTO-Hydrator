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

class UserDTO
{
    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:2')]
    public string $name = '';

    #[MapFrom('email_address', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email = '';

    #[MapFrom('age', caster: 'int', required: false, default: 18)]
    #[Validate('integer')]
    #[Validate('min:13')]
    public int $age = 18;

    #[MapFrom('is_active', caster: 'bool', required: false, default: true)]
    public bool $isActive = true;

    #[MapFrom('tags', caster: 'array', required: false, default: [])]
    public array $tags = [];

    #[MapFrom('created_at', caster: 'datetime', readonly: true)]
    public ?\DateTimeImmutable $createdAt = null;

    #[MapFrom('password', caster: 'string', writeonly: true)]
    #[Validate('required')]
    #[Validate('min:8')]
    public string $password = '';
}

class HydratorTest extends TestCase
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

    public function testHydrateBasic(): void
    {
        $data = [
            'name' => '  John Doe  ',
            'email_address' => 'JOHN@EXAMPLE.COM',
            'age' => '25',
            'is_active' => 'true',
            'password' => 'secret123',
        ];

        $dto = $this->hydrator->hydrate(UserDTO::class, $data);

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals(25, $dto->age);
        $this->assertTrue($dto->isActive);
        $this->assertEquals('secret123', $dto->password);
    }

    public function testHydrateWithDefaults(): void
    {
        $data = [
            'name' => 'Jane',
            'email_address' => 'jane@test.com',
            'password' => 'password123',
        ];

        $dto = $this->hydrator->hydrate(UserDTO::class, $data);

        $this->assertEquals(18, $dto->age);
        $this->assertTrue($dto->isActive);
        $this->assertEquals([], $dto->tags);
    }

    public function testHydrateAndValidateSuccess(): void
    {
        $data = [
            'name' => 'Valid User',
            'email_address' => 'valid@test.com',
            'password' => 'password123',
        ];

        $dto = $this->hydrator->hydrateAndValidate(UserDTO::class, $data);

        $this->assertInstanceOf(UserDTO::class, $dto);
    }

    public function testHydrateAndValidateFailure(): void
    {
        $data = [
            'name' => 'A', // too short
            'email_address' => 'invalid-email',
            'password' => 'short', // too short
        ];

        $this->expectException(\Hydrastat\DtoHydrator\Exceptions\ValidationException::class);
        
        $this->hydrator->hydrateAndValidate(UserDTO::class, $data);
    }

    public function testExtract(): void
    {
        $dto = new UserDTO();
        $dto->name = 'Test User';
        $dto->email = 'test@example.com';
        $dto->age = 30;
        $dto->isActive = false;
        $dto->tags = ['tag1', 'tag2'];
        $dto->createdAt = new \DateTimeImmutable('2024-01-01');
        $dto->password = 'secret'; // writeonly

        $extracted = $this->hydrator->extract($dto);

        $this->assertEquals('Test User', $extracted['name']);
        $this->assertEquals('test@example.com', $extracted['email_address']);
        $this->assertEquals(30, $extracted['age']);
        $this->assertFalse($extracted['is_active']);
        $this->assertEquals(['tag1', 'tag2'], $extracted['tags']);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $extracted['created_at']);
        $this->assertArrayNotHasKey('password', $extracted); // writeonly
    }

    public function testExtractWithGroups(): void
    {
        $dto = new UserDTO();
        $dto->name = 'Test';
        $dto->email = 'test@test.com';

        // Default group only
        $extracted = $this->hydrator->extract($dto, ['groups' => ['default']]);
        $this->assertArrayHasKey('name', $extracted);
    }

    public function testExtractExcludeNull(): void
    {
        $dto = new UserDTO();
        $dto->name = 'Test';
        $dto->email = 'test@test.com';
        // age, isActive have default values (not null)
        // tags has default empty array (not null)
        $dto->createdAt = null; // explicitly null

        $extracted = $this->hydrator->extract($dto, ['exclude_null' => true]);
        
        // age=18, is_active=true, tags=[] are not null, so they're included
        $this->assertEquals(18, $extracted['age']);
        $this->assertTrue($extracted['is_active']);
        $this->assertEquals([], $extracted['tags']);
        // createdAt is null, so excluded
        $this->assertArrayNotHasKey('created_at', $extracted);
    }

    public function testTypeCasters(): void
    {
        $caster = new TypeCaster();

        $this->assertEquals(42, $caster->cast('int', '42'));
        $this->assertEquals(3.14, $caster->cast('float', '3.14'));
        $this->assertTrue($caster->cast('bool', 'true'));
        $this->assertFalse($caster->cast('bool', 'false'));
        $this->assertEquals('trimmed', $caster->cast('trim', '  trimmed  '));
        $this->assertEquals('lowercase', $caster->cast('lower', 'LOWERCASE'));
        $this->assertEquals('test@example.com', $caster->cast('email', 'TEST@EXAMPLE.COM'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $caster->cast('datetime', '2024-01-01'));
    }

    public function testCustomCaster(): void
    {
        $caster = new TypeCaster();
        $caster->register('uppercase', fn($v) => strtoupper((string)$v));

        $this->assertEquals('HELLO', $caster->cast('uppercase', 'hello'));
    }

    public function testValidatorCustomRule(): void
    {
        $validator = new Validator();
        $validator->register('custom_rule', fn($v) => strlen($v) > 5 ? null : 'Too short');

        $dto = new class {
            #[Validate('custom_rule')]
            public string $field = 'long enough';
        };

        $result = $validator->validate($dto);
        $this->assertTrue($result->valid);

        $dto->field = 'short';
        $result = $validator->validate($dto);
        $this->assertFalse($result->valid);
        $this->assertEquals('Too short', $result->getFirstError('field'));
    }
}