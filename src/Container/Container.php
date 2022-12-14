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

namespace Lightning\Container;

use Closure;
use Lightning\Autowire\Autowire;
use Psr\Container\ContainerInterface;
use Lightning\Container\Exception\NotFoundException;

/**
 * A lightweight PSR-11 Dependency Injection Container
 *
 * @see https://www.php-fig.org/psr/psr-11/
 */
class Container implements ContainerInterface
{
    protected ?Autowire $autowire = null;

    protected array $definitions = [];
    private array $instances = [];
    protected bool $autoConfigure = false;

    /**
     * Constructor
     *
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $key => $value) {
            is_int($key) ? $this->register($value) : $this->register($key, $value);
        }
    }

    /**
     * Enables the Autowiring
     *
     * @return static
     */
    public function enableAutowiring(): static
    {
        $this->autowire = $this->createAutowire();

        return $this;
    }

    private function createAutowire(): Autowire
    {
        return new Autowire($this);
    }

    /**
     * Automatically configure services, if class exists it will try to resolve it.
     *
     * @return static
     */
    public function enableAutoConfigure(): static
    {
        $this->autoConfigure = true;

        return $this;
    }

    /**
     * Adds an item to the Container, each time get is called for this service the object will be created
     *
     * @param string $id e.g ServerRequest::class, Logger::class
     * @param string|closure|object $concrete
     * @return static
     */
    public function register(string $id, $concrete = null): static
    {
        $concrete = $concrete ?: $id;

        $this->definitions[$id] = $concrete;

        return $this;
    }

    /**
     * Gets an entry from the Container
     *
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException(sprintf('No defintion found for `%s`', $id));
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->resolveDefinition($id, true);
    }

    /**
     * This will resolve the object each time it is called
     *
     * @param string $id
     * @return mixed
     */
    public function resolve(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException(sprintf('No defintion found for `%s`', $id));
        }

        return $this->resolveDefinition($id, false);
    }

    /**
     * Resolves an entry in the container
     *
     * @param string $id
     * @param boolean $share
     * @return mixed
     */
    private function resolveDefinition(string $id, bool $share = true): mixed
    {
        $concrete = $this->definitions[$id] ?? $id;

        if (is_callable($concrete)) {
            $concrete = $concrete($this);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $concrete = $this->autowire ? $this->autowire->class($concrete) : new $concrete();
        }

        if ($share) {
            $this->instances[$id] = $concrete;
        }

        return $concrete;
    }

    /**
     * Checks if the container has a definition
     *
     * @param string $id
     * @return boolean
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || ($this->autoConfigure && (class_exists($id)));
    }
}
