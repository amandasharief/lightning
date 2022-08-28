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

trait MiddlewareTrait
{
    /**
     * @var \Psr\Http\Server\MiddlewareInterface[] $middlewares
     */
    protected array $middlewares = [];

    /**
     * Adds middleware to queue
     *
     * @param MiddlewareInterface $middleware
     * @return static
     */
    public function middleware(MiddlewareInterface $middleware): static
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Adds middleware to the start of queue
     *
     * @param MiddlewareInterface $middleware
     * @return static
     */
    public function prependMiddleware(MiddlewareInterface $middleware): static
    {
        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    /**
     * @return \Psr\Http\Server\MiddlewareInterface[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
