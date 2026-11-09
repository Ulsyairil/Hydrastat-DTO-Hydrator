<?php

namespace Hydrastat\DtoHydrator\Integrations\Yii;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator as CoreHydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Hydrastat\DtoHydrator\Integrations\Yii\Config\Hydrator as HydratorConfig;
use Yii;

/**
 * Builds a Hydrator from Yii application params or an explicit config object.
 */
class Service
{
    public static function hydrator(?HydratorConfig $config = null): HydratorInterface
    {
        $config ??= self::config();

        $propertyAccessor = new PropertyAccessor($config->allowPrivateAccess);
        $typeCaster = new TypeCaster();
        $validator = new Validator();

        foreach ($config->customValidators as $name => $callable) {
            $validator->register($name, $callable);
        }

        foreach ($config->customCasters as $name => $callable) {
            $typeCaster->register($name, $callable);
        }

        return new CoreHydrator(
            new MappingResolver($propertyAccessor),
            $propertyAccessor,
            $typeCaster,
            $validator,
            $config->defaultOptions,
        );
    }

    public static function config(): HydratorConfig
    {
        $config = Yii::$app?->params['hydrator'] ?? null;

        if ($config instanceof HydratorConfig) {
            return $config;
        }

        if (is_array($config)) {
            return new HydratorConfig($config);
        }

        return new HydratorConfig();
    }
}
