<?php

namespace Hydrastat\DtoHydrator\Integrations\CakePHP;

use Cake\Core\Configure;
use Cake\Core\Container;
use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator as CoreHydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Hydrastat\DtoHydrator\Integrations\CakePHP\Config\Hydrator as HydratorConfig;

/**
 * Builds a Hydrator from `Configure::read('Hydrator')` or an explicit config object.
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
        $config = Configure::read('Hydrator');

        if ($config instanceof HydratorConfig) {
            return $config;
        }

        if (is_array($config)) {
            return new HydratorConfig($config);
        }

        return new HydratorConfig();
    }

    /**
     * Register shared hydrator definitions in a CakePHP PSR-11 container.
     *
     * ```php
     * $container = new \Cake\Core\Container();
     * \Hydrastat\DtoHydrator\Integrations\CakePHP\Service::register($container);
     * $hydrator = $container->get(\Hydrastat\DtoHydrator\Contracts\HydratorInterface::class);
     * ```
     */
    public static function register(Container $container, ?HydratorConfig $config = null): void
    {
        $config ??= self::config();

        $container->addShared(
            HydratorInterface::class,
            static fn (): HydratorInterface => self::hydrator($config),
        );

        $container->addShared(
            'hydrator',
            static fn (): HydratorInterface => $container->get(HydratorInterface::class),
        );
    }
}
