<?php

/**
 * Performance benchmark for Hydrastat DTO Hydrator
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Hydrastat\DtoHydrator\Mapping\Attribute\MapFrom;
use Hydrastat\DtoHydrator\Mapping\Attribute\Validate;

class BenchmarkDTO
{
    #[MapFrom('id', caster: 'int')]
    public int $id = 0;

    #[MapFrom('name', caster: 'trim')]
    #[Validate('required')]
    #[Validate('min:2')]
    #[Validate('max:100')]
    public string $name = '';

    #[MapFrom('email', caster: 'email')]
    #[Validate('required')]
    #[Validate('email')]
    public string $email = '';

    #[MapFrom('age', caster: 'int', required: false, default: 18)]
    #[Validate('integer')]
    #[Validate('min:13')]
    public int $age = 18;

    #[MapFrom('is_active', caster: 'bool', required: false, default: true)]
    public bool $isActive = true;

    #[MapFrom('balance', caster: 'float', required: false, default: 0.0)]
    public float $balance = 0.0;

    #[MapFrom('tags', caster: 'array', required: false, default: [])]
    public array $tags = [];

    #[MapFrom('created_at', caster: 'datetime', readonly: true)]
    public ?\DateTimeImmutable $createdAt = null;

    #[MapFrom('metadata', caster: 'json', required: false, default: '{}')]
    public array $metadata = [];
}

function benchmark(callable $fn, int $iterations): float
{
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $end = hrtime(true);
    return ($end - $start) / 1_000_000; // Convert to milliseconds
}

$hydrator = new Hydrator(
    new MappingResolver(new PropertyAccessor()),
    new PropertyAccessor(),
    new TypeCaster(),
    new Validator()
);

$data = [
    'id' => '123',
    'name' => '  John Doe  ',
    'email' => 'JOHN@EXAMPLE.COM',
    'age' => '30',
    'is_active' => 'true',
    'balance' => '1234.56',
    'tags' => ['user', 'premium'],
    'created_at' => '2024-01-15T10:30:00Z',
    'metadata' => '{"source": "web", "campaign": "summer"}',
];

$iterations = 10000;

echo "Hydrastat DTO Hydrator Benchmark\n";
echo "================================\n";
echo "Iterations: {$iterations}\n\n";

// Warmup
for ($i = 0; $i < 100; $i++) {
    $hydrator->hydrate(BenchmarkDTO::class, $data);
}

// Benchmark hydration
$hydrateTime = benchmark(function () use ($hydrator, $data) {
    $hydrator->hydrate(BenchmarkDTO::class, $data);
}, $iterations);

echo "Hydration:     {$hydrateTime} ms total, " . round($hydrateTime / $iterations, 4) . " ms/op\n";

// Benchmark hydration + validation
$hydrateValidateTime = benchmark(function () use ($hydrator, $data) {
    $hydrator->hydrateAndValidate(BenchmarkDTO::class, $data);
}, $iterations);

echo "Hydrate+Valid: {$hydrateValidateTime} ms total, " . round($hydrateValidateTime / $iterations, 4) . " ms/op\n";

// Benchmark extraction
$dto = $hydrator->hydrate(BenchmarkDTO::class, $data);

$extractTime = benchmark(function () use ($hydrator, $dto) {
    $hydrator->extract($dto);
}, $iterations);

echo "Extraction:    {$extractTime} ms total, " . round($extractTime / $iterations, 4) . " ms/op\n";

echo "\nMemory usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";