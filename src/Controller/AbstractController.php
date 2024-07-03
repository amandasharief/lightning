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

namespace Lightning\Controller;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Lightning\TemplateRenderer\TemplateRendererInterface;

/**
 * Abstract Controller
 *
 * @internal design has been changed with hook methods added rather than hard coding events etc, these can be overridden with a trait to get
 * the behavior that you want, eg. PSR-14 events
 */
abstract class AbstractController
{
    /**
     * Constructor - if you override this make sure still call this
     * @param TemplateRenderInterface $view This has been called view for easy
     */
    public function __construct(protected TemplateRendererInterface $view)
    {
        if ($this instanceof ControllerLifecycleInterface) {
            $this->initialize();
        }
    }

    /**
     * Renders a template using the View package
     *
     * @param string $template e.g. articles/index
     */
    public function render(string $template, array $data = [], int $statusCode = 200): ResponseInterface
    {
        if ($this instanceof ControllerLifecycleInterface && $response = $this->beforeRender()) {
            return $response;
        }

        $response = $this->createResponse()
            ->withHeader('Content-Type', 'text/html')
            ->withStatus($statusCode);

        $response->getBody()->write(
            $this->view->render($template, $data)
        );

        return $this instanceof ControllerLifecycleInterface ? $this->afterRender($response) : $response;
    }

    /**
     * Renders a JSON response
     */
    public function renderJson($payload, int $statusCode = 200, int $jsonFlags = 0): ResponseInterface
    {
        if ($this instanceof ControllerLifecycleInterface && $response = $this->beforeRender()) {
            return $response;
        }

        $response = $this->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);

        $response->getBody()->write(
            json_encode($payload, $jsonFlags)
        );

        return $this instanceof ControllerLifecycleInterface ? $this->afterRender($response) : $response;
    }

    /**
     * Sends a file as a Response
     */
    public function renderFile(string $path, array $options = []): ResponseInterface
    {
        if (strpos($path, '../') !== false) {
            throw new InvalidArgumentException(sprintf('`%s` is a relative path', $path));
        }

        if (! is_file($path)) {
            throw new InvalidArgumentException(sprintf('`%s` does not exist or is not a file', $path));
        }

        if ($this instanceof ControllerLifecycleInterface && $response = $this->beforeRender()) {
            return $response;
        }

        $response = $this->createResponse()
            ->withStatus(200)
            ->withHeader('Content-Type', mime_content_type($path))
            ->withHeader('Content-Length', (string) filesize($path) ?: 0);

        if ($options['download'] ?? true) {
            $response = $response->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $options['name'] ?? basename($path)));
        }

        $response->getBody()->write(file_get_contents($path));

        return $this instanceof ControllerLifecycleInterface ? $this->afterRender($response) : $response;
    }

    /*
     * Sets the response as a redirect, return this from your Controller action
     *
     * @param string $uri e.g /articles or https://app.test/articles
     */
    public function redirect(string $uri, int $status = 302): ResponseInterface
    {
        if ($this instanceof ControllerLifecycleInterface && $response = $this->beforeRedirect($uri)) {
            return $response;
        }

        $response = $this->createResponse()
            ->withHeader('Location', $uri)
            ->withStatus($status);

        return  $this instanceof ControllerLifecycleInterface ? $this->afterRedirect($response) : $response;
    }

    /**
     * Factory method
     */
    abstract public function createResponse(): ResponseInterface;
}
