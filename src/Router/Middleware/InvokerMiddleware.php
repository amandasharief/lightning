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

namespace Lightning\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lightning\Router\Exception\RouterException;

class InvokerMiddleware implements MiddlewareInterface
{
    private $callable;

    /**
     * Constructor
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Processes the incoming request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callable = $this->callable;

        $response = $callable($request);

        if (! $response instanceof ResponseInterface) {
            throw new RouterException('No response was returned');
        }

        return $response;
    }
}
