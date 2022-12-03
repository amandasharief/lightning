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
     */
    public function get(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a POST route
     */
    public function post(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a PATCH route
     */
    public function patch(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a PUT route
     */
    public function put(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a DELETE route
     */
    public function delete(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a HEAD route
     */
    public function head(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Creates a OPTIONS route
     */
    public function options(string $path, callable|array|string $handler, array $constraints = []): Route;

    /**
     * Adds a middleware to the queue
     */
    public function middleware(MiddlewareInterface $middleware): RoutesInterface;

    /**
     *  Adds a middleware to the start of queue
     */
    public function prependMiddleware(MiddlewareInterface $middleware): RoutesInterface;
}
