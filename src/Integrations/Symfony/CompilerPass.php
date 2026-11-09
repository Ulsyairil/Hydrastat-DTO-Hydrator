<?php

namespace Hydrastat\DtoHydrator\Integrations\Symfony;

use Hydrastat\DtoHydrator\Contracts\HydratorInterface;
use Hydrastat\DtoHydrator\Contracts\MappingProviderInterface;
use Hydrastat\DtoHydrator\Contracts\ValidatorInterface;
use Hydrastat\DtoHydrator\Core\Hydrator;
use Hydrastat\DtoHydrator\Core\MappingResolver;
use Hydrastat\DtoHydrator\Core\PropertyAccessor;
use Hydrastat\DtoHydrator\Core\TypeCaster;
use Hydrastat\DtoHydrator\Core\Validator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->register(MappingProviderInterface::class, MappingResolver::class)
            ->setArguments([new Reference(PropertyAccessor::class)]);

        $container->register(ValidatorInterface::class, Validator::class);

        $container->register(PropertyAccessor::class, PropertyAccessor::class)
            ->setArguments([$container->getParameter('hydrator.allow_private_access', true)]);

        $container->register(TypeCaster::class, TypeCaster::class);

        $container->register(HydratorInterface::class, Hydrator::class)
            ->setArguments([
                new Reference(MappingProviderInterface::class),
                new Reference(PropertyAccessor::class),
                new Reference(TypeCaster::class),
                new Reference(ValidatorInterface::class),
                $container->getParameter('hydrator.default_options', []),
            ]);

        $container->setAlias('hydrastat.hydrator', HydratorInterface::class);
    }
}