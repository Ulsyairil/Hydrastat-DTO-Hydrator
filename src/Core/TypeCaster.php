<?php

namespace Hydrastat\DtoHydrator\Core;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class TypeCaster
{
    /** @var array<string, callable(mixed): mixed> */
    private array $casters = [];

    public function __construct()
    {
        $this->registerBuiltInCasters();
    }

    public function register(string $name, callable $caster): void
    {
        $this->casters[$name] = $caster;
    }

    public function has(string $name): bool
    {
        return isset($this->casters[$name]);
    }

    public function cast(string $name, mixed $value): mixed
    {
        if (!isset($this->casters[$name])) {
            throw new \InvalidArgumentException("Caster '{$name}' not registered");
        }

        return ($this->casters[$name])($value);
    }

    public function castIfRegistered(string $name, mixed $value): mixed
    {
        if (!isset($this->casters[$name])) {
            return $value;
        }

        return ($this->casters[$name])($value);
    }

    private function registerBuiltInCasters(): void
    {
        $this->casters = [
            'int'       => fn($v) => $v === '' || $v === null ? null : (int)$v,
            'integer'   => fn($v) => $v === '' || $v === null ? null : (int)$v,
            'float'     => fn($v) => $v === '' || $v === null ? null : (float)$v,
            'double'    => fn($v) => $v === '' || $v === null ? null : (float)$v,
            'bool'      => fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'boolean'   => fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string'    => fn($v) => $v === null ? null : (string)$v,
            'trim'      => fn($v) => is_string($v) ? trim($v) : $v,
            'lower'     => fn($v) => is_string($v) ? strtolower($v) : $v,
            'upper'     => fn($v) => is_string($v) ? strtoupper($v) : $v,
            'email'     => fn($v) => is_string($v) ? strtolower(trim($v)) : $v,
            'array'     => fn($v) => is_array($v) ? $v : ($v === null ? [] : [(string)$v]),
            'json'      => fn($v) => is_string($v) ? json_decode($v, true) : $v,
            'datetime'  => [$this, 'castDateTime'],
            'date'      => [$this, 'castDate'],
            'timestamp' => fn($v) => $v === '' || $v === null ? null : (int)$v,
        ];
    }

    public function castDateTime(mixed $value): ?DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_numeric($value)) {
            return (new DateTimeImmutable())->setTimestamp((int)$value);
        }

        if (is_string($value)) {
            try {
                return new DateTimeImmutable($value);
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }

    public function castDate(mixed $value): ?DateTimeInterface
    {
        $dt = $this->castDateTime($value);
        if ($dt === null) {
            return null;
        }
        return $dt->setTime(0, 0, 0);
    }
}