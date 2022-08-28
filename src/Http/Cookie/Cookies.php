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

namespace Lightning\Http\Cookie;

use Countable;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Cookies implements Countable, IteratorAggregate
{
    protected array $cookies = [];

    /**
     * @var Cookie[]
     */
    protected array $cookiesToSet = [];

    /**
     * Constructor
     *
     * @param array $cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->cookies = $cookies;
    }

    /**
     * Gets a value of a cookie
     *
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function get(string $name, ?string $default = null): string|null
    {
        return array_key_exists($name, $this->cookies) ? $this->cookies[$name] : $default;
    }

    /**
     * Checks the request has a cookie
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->cookies);
    }

    /**
     * Adds a cookie to the response
     *
     * @param Cookie $cookie
     * @return static
     */
    public function add(Cookie $cookie): static
    {
        $this->cookiesToSet[] = $cookie;

        return $this;
    }

    /**
     * Deletes a cookie for the next request
     *
     * @param Cookie $cookie
     * @return static
     */
    public function delete(Cookie $cookie): static
    {
        $this->cookiesToSet[] = $cookie->setMaxAge(-1)->setValue('');

        return $this;
    }

    /**
     * Adds cookies that were set to the Response object
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->cookiesToSet as $cookie) {
            $response = $cookie->addToResponse($response);
        }

        return $response;
    }

    /**
     * Sets the ServerRequest to read cookies from
     *
     * @param ServerRequestInterface $request
     * @return static
     */
    public function setServerRequest(ServerRequestInterface $request): static
    {
        $this->cookies = $request->getCookieParams();

        return $this;
    }

    /**
     * Gets the cookies from this request
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Gets the cookie count from this request
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->cookies);
    }
}
