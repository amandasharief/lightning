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

namespace Lightning\Http\Cookie\Middleware;

use Lightning\Http\Cookie\Cookies;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CookieMiddleware implements MiddlewareInterface
{
    protected Cookies $cookies;

    /**
     * Constructor
     *
     * @param Cookies $cookies
     */
    public function __construct(Cookies $cookies)
    {
        $this->cookies = $cookies;
    }
    /**
     * Undocumented function
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('cookies', $this->cookies);

        return $this->cookies
            ->setServerRequest($request)
            ->addToResponse($handler->handle($request));
    }
}
