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

namespace Lightning\Router;

class RouteCollection implements RoutesInterface
{
    use MiddlewareTrait;
    use RouteTrait;

    /**
    * Collection of Route
    *
    * @var Route[]
    */
    protected array $routes = [];

    protected ?string $prefix = null;

    protected ?object $callback;

    protected string $pattern;

    /**
     * Prefix
     */
    public function __construct(string $prefix = null, callable $callback = null)
    {
        $this->prefix = $prefix;
        $this->callback = $callback;

        $pattern = $this->prefix ? preg_replace('/\//', '\\/', $this->prefix) : '';   // Escape forward slashes for ReGex
        $this->pattern = "/^{$pattern}($|\\/)/"; // '/admin' or /admin/* Removed case insensitive
    }

    /**
     * Checks if the route group is match
     */
    public function matchPrefix(string $uri): bool
    {
        return (bool) preg_match($this->pattern, $uri);
    }

    /**
     * Creates the regular expression and add to routes
     */
    public function map(string $method, string $path, callable|array|string $handler, array $constraints): Route
    {
        return $this->routes[] = $this->createRoute(
            $method, sprintf('%s/%s', $this->prefix, trim($path, '/')), $handler, $constraints
        );
    }

    /**
     * Factory method
     */
    private function createRoute(string $method, string $path, callable|array|string $handler, array $constraints = []): Route
    {
        return new Route($method, $path, $handler, $constraints);
    }

    /**
     * Gets the Routes
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        if ($this->callback) {
            $collection = $this->callback;
            $collection($this);
        }

        return $this->routes;
    }
}
