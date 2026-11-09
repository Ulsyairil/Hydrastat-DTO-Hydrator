<?php

namespace Hydrastat\DtoHydrator\Integrations\Laravel;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Illuminate\Support\Facades\Facade;

class Facade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HydratorInterface::class;
    }
}