<?php

namespace Hydrastat\DtoHydrator\Integrations\CodeIgniter;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use CodeIgniter\Config\BaseConfig;

class Service
{
    public static function hydrator(?BaseConfig $config = null): HydratorInterface
    {
        $config = $config ?? config('Hydrator');
        
        $propertyAccessor = new PropertyAccessor($config->allowPrivateAccess ?? true);
        $mappingProvider = new MappingResolver($propertyAccessor);
        $typeCaster = new TypeCaster();
        $validator = new Validator();

        if (!empty($config->customValidators)) {
            foreach ($config->customValidators as $name => $callable) {
                $validator->register($name, $callable);
            }
        }

        if (!empty($config->customCasters)) {
            foreach ($config->customCasters as $name => $callable) {
                $typeCaster->register($name, $callable);
            }
        }

        return new Hydrator(
            $mappingProvider,
            $propertyAccessor,
            $typeCaster,
            $validator,
            $config->defaultOptions ?? []
        );
    }
}