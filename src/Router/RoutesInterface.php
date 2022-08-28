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

use Psr\Http\Server\MiddlewareInterface;

interface RoutesInterface
{
    /**
     * Creates a GET route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function get(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a POST route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function post(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a PATCH route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function patch(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a PUT route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function put(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a DELETE route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function delete(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a HEAD route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function head(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a OPTIONS route
     *
     * @param string $path
     * @param callable|array|string $handler
     * @param array $constraints
     * @return Route
     */
    public function options(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Adds a middleware to the queue
     *
     * @param MiddlewareInterface $middleware
     * @return RoutesInterface
     */
    public function middleware(MiddlewareInterface $middleware): RoutesInterface;

    /**
     *  Adds a middleware to the start of queue
     *
     * @param MiddlewareInterface $middleware
     * @return RoutesInterface
     */
    public function prependMiddleware(MiddlewareInterface $middleware): RoutesInterface;
}
