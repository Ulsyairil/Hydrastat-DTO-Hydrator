<?php

namespace Hydrastat\DtoHydrator\Integrations\Yii;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator as CoreHydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Yii;
use yii\base\BootstrapInterface;
use yii\di\Container;

/**
 * Registers hydrator definitions in the Yii DI container.
 *
 * Add it to `config/main.php`:
 *
 * ```php
 * 'bootstrap' => [\Hydrastat\DtoHydrator\Integrations\Yii\Bootstrap::class],
 * ```
 *
 * Then resolve it anywhere:
 *
 * ```php
 * $hydrator = Yii::$container->get(\Hydrastat\DtoHydrator\Contracts\HydratorInterface::class);
 * // or
 * $hydrator = Yii::$container->get('hydrator');
 * ```
 */
class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $config = Service::config();
        $container = Yii::$container;

        $container->setSingleton(PropertyAccessor::class, static function () use ($config): PropertyAccessor {
            return new PropertyAccessor($config->allowPrivateAccess);
        });

        $container->setSingleton(MappingProviderInterface::class, static function (Container $container): MappingResolver {
            return new MappingResolver($container->get(PropertyAccessor::class));
        });

        $container->setSingleton(ValidatorInterface::class, static function () use ($config): Validator {
            $validator = new Validator();

            foreach ($config->customValidators as $name => $callable) {
                $validator->register($name, $callable);
            }

            return $validator;
        });

        $container->setSingleton(TypeCaster::class, static function () use ($config): TypeCaster {
            $caster = new TypeCaster();

            foreach ($config->customCasters as $name => $callable) {
                $caster->register($name, $callable);
            }

            return $caster;
        });

        $container->setSingleton(HydratorInterface::class, static function (Container $container) use ($config): CoreHydrator {
            return new CoreHydrator(
                $container->get(MappingProviderInterface::class),
                $container->get(PropertyAccessor::class),
                $container->get(TypeCaster::class),
                $container->get(ValidatorInterface::class),
                $config->defaultOptions,
            );
        });

        $container->setSingleton('hydrator', static function (Container $container): HydratorInterface {
            return $container->get(HydratorInterface::class);
        });
    }
}
