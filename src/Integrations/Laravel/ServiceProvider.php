<?php

namespace Hydrastat\DtoHydrator\Integrations\Laravel;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Illuminate\Support\ServiceProvider;

class ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/hydrator.php',
            'hydrator'
        );

        $this->app->singleton(MappingProviderInterface::class, function ($app) {
            return new MappingResolver(
                new PropertyAccessor($app->make('config')->get('hydrator.allow_private_access', true))
            );
        });

        $this->app->singleton(ValidatorInterface::class, function ($app) {
            $validator = new Validator();
            
            foreach ($app->make('config')->get('hydrator.custom_validators', []) as $name => $callable) {
                $validator->register($name, $callable);
            }

            return $validator;
        });

        $this->app->singleton(HydratorInterface::class, function ($app) {
            return new Hydrator(
                $app->make(MappingProviderInterface::class),
                new PropertyAccessor($app->make('config')->get('hydrator.allow_private_access', true)),
                new TypeCaster(),
                $app->make(ValidatorInterface::class),
                $app->make('config')->get('hydrator.default_options', [])
            );
        });

        $this->app->alias(HydratorInterface::class, 'hydrator');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/hydrator.php' => config_path('hydrator.php'),
        ], 'hydrator-config');
    }
}