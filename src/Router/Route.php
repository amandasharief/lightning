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

use Psr\Container\ContainerInterface;
use Lightning\Router\Exception\RouterException;

class Route
{
    use MiddlewareTrait;

    protected string $method;
    protected string $path;
    protected string $pattern;
    protected array $constraints = [];
    protected array $variables = [];
    protected ?string $uri = null;

    private $handler;

    /**
     * Constructor
     */
    public function __construct(string $method, string $path, callable|array|string $handler, array $constraints = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->constraints = $constraints;

        $pattern = preg_replace('/\//', '\\/', $this->path);   // Escape forward slashes for ReGex
        $pattern = preg_replace('/\:([a-z]+)/i', '(?P<\1>[^\/]+)', $pattern);  // Convert vars e.g. :id :name
        $this->pattern = "/^{$pattern}$/";
    }

    /**
     * Matches a route and params to this object
     */
    public function match(string $method, string $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $matches = $variables = [];

        if (! preg_match($this->pattern, $uri, $matches)) {
            return false;
        }

        $this->uri = $uri;

        // extract params
        foreach ($matches as $key => $match) {
            if (is_string($key)) {
                $variables[$key] = ctype_digit($match) ? (int) $match : $match;
            }
        }

        // process constriants
        foreach ($this->constraints as $attribute => $pattern) {
            if (isset($variables[$attribute]) && ! preg_match('#^' . $pattern . '$#', (string) $variables[$attribute])) {
                return false;
            };
        }

        $this->variables = $variables;

        return true;
    }

    /**
     * Gets the Method for this Route
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the Path for this Route
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the matched URI
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Get the Route vars
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Gets the Constraints
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Gets the handler for this route
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Invokes the handler for the route
     */
    public function __invoke(?ContainerInterface $container = null): callable
    {
        $handler = $this->handler;

        // convert 'App\Http\Articles\Controller::index' proxy to [App\Http\Articles\Controller::class,'index'];
        if (is_string($handler) && strpos($handler, '::') !== false) {
            $handler = explode('::', $handler);
        }

        // Convert  [App\Http\Articles\Controller::class,'index'] to [object,'index']
        if (is_array($handler) && is_string($handler[0])) {
            $handler = [$this->resolve($handler[0], $container), $handler[1]];
        } elseif (is_string($handler)) {
            $handler = $this->resolve($handler, $container);
        }

        if (is_callable($handler)) {
            return $this->handler = $handler;
        }

        throw new RouterException(
            sprintf('The handler for `%s %s` is not a callable', $this->method, $this->path)
        );
    }

    /**
     * Resolves the class, if it is not in the container then the container will be passed.
     */
    private function resolve(string $class, ?ContainerInterface $container = null): object
    {
        if ($container && $container->has($class)) {
            return $container->get($class);
        }

        if (! class_exists($class)) {
            throw new RouterException(sprintf('Error resolving `%s`', $class), 404);
        }

        return new $class();
    }
}
