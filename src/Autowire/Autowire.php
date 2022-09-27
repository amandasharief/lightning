<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2022 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Autowire;

use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Psr\Container\ContainerInterface;
use Lightning\Autowire\Exception\AutowireException;

/**
 * Autowire - Automatically create and inject dependencies compatible with PSR-11
 */
class Autowire
{
    protected ?ContainerInterface $container;

    /**
     * Constructor
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Sets the PSR-11 Container to be used
     */
    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Autowires a class using the constructor method
     */
    public function class(string $class, array $parameters = []): object
    {
        if (! class_exists($class)) {
            throw new AutowireException(sprintf('`%s` could not be found', $class));
        }

        $reflection = new ReflectionClass($class);
        if (! $reflection->isInstantiable()) {
            throw new AutowireException(sprintf('Class `%s` is not instantiable', $class));
        }

        $reflectionMethod = $reflection->getConstructor();

        return $reflection->newInstanceArgs(
            $this->resolveParameters($reflectionMethod ? $reflectionMethod->getParameters() : [], $parameters)
        );
    }

    /**
     * Invokes a method on the object
     */
    public function method(object $object, string $method, array $parameters = []): mixed
    {
        if (! method_exists($object, $method)) {
            throw new AutowireException(
                sprintf('`%s` does not have the `%s` method', $object::class, $method)
            );
        }

        $reflection = (new ReflectionClass($object))->getMethod($method);

        $vars = $this->resolveParameters($reflection->getParameters(), $parameters);

        return call_user_func_array([$object,$method], $vars);
    }

    /**
     * Autowires a function
     */
    public function function($function, array $parameters = []): mixed
    {
        $reflectionFunction = new ReflectionFunction($function);

        return call_user_func_array(
            $function, $this->resolveParameters($reflectionFunction->getParameters(), $parameters)
        );
    }

    /**
     * Resolves an array of parameters
    */
    protected function resolveParameters(array $parameters, array $vars = []): array
    {
        return array_map(function ($parameter) use ($vars) {
            return $this->resolveParameter($parameter, $vars);
        }, $parameters);
    }

    /**
     * Resolves a paramater
     */
    protected function resolveParameter(ReflectionParameter $parameter, array $vars = []): mixed
    {
        /** @var \ReflectionNamedType|\ReflectionUnionType|null $parameterType */
        $parameterType = $parameter->getType();

        if (! $parameterType) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new AutowireException(
                sprintf('constructor parameter `%s` has no type or default value', $parameter->name)
            );
        }

        $hasDefaultValue = $parameter->isDefaultValueAvailable();

        if ($parameterType->isBuiltin()) {
            if (isset($vars[$parameter->name])) {
                return $vars[$parameter->name];
            }
            if ($hasDefaultValue) {
                return $parameter->getDefaultValue();
            }

            throw new AutowireException(sprintf('parameter `%s` has no default value', $parameter->name));
        }

        $service = $parameterType->getName();

        if ($this->container && $this->container->has($service)) {
            return $this->container->get($service);
        } elseif (class_exists($service)) {
            return $this->class($service);
        }

        if (isset($vars[$service])) {
            return $vars[$service];
        }

        if ($hasDefaultValue) {
            return $parameter->getDefaultValue();
        }

        throw new AutowireException(sprintf('Class `%s` not found', $service));
    }
}