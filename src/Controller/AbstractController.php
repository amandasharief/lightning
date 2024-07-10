<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Controller;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\Event\AfterRender;
use Lightning\Controller\Event\BeforeRender;
use Lightning\Controller\Event\AfterRedirect;
use Lightning\Controller\Event\BeforeRedirect;
use Lightning\TemplateRenderer\TemplateRendererInterface;

/**
 * Abstract Controller
 */
abstract class AbstractController
{
    /**
     * Constructor - if you override this make sure still call this
     * @param TemplateRendererInterface $view This has been called view for easy
     */
    public function __construct(
        protected TemplateRendererInterface $view
    ) {
        $this->initialize();
    }

    /**
     * Hook that is triggered during startup
     */
    protected function initialize(): void
    {
    }

    /**
     * Renders a template using the View package
     *
     * @param string $template e.g. articles/index
     */
    public function render(string $template, array $data = [], int $statusCode = 200): ResponseInterface
    {
        if ($response = $this->beforeRender()) {
            return $response;
        }

        $response = $this->createResponse()
            ->withHeader('Content-Type', 'text/html')
            ->withStatus($statusCode);

        $response->getBody()->write(
            $this->view->render($template, $data)
        );

        return $this->afterRender($response);
    }

    /**
     * Renders a JSON response
     */
    public function renderJson($payload, int $statusCode = 200, int $jsonFlags = 0): ResponseInterface
    {
        if ($response = $this->beforeRender()) {
            return $response;
        }

        $response = $this->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);

        $response->getBody()->write(
            json_encode($payload, $jsonFlags)
        );

        return $this->afterRender($response);
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

        if ($response = $this->beforeRender()) {
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

        return $this->afterRender($response);
    }

    /*
     * Sets the response as a redirect, return this from your Controller action
     *
     * @param string $uri e.g /articles or https://app.test/articles
     */
    public function redirect(string $uri, int $status = 302): ResponseInterface
    {
        if ($response = $this->beforeRedirect($uri)) {
            return $response;
        }


        $response = $this->createResponse()
            ->withHeader('Location', $uri)
            ->withStatus($status);

        return $this->afterRedirect($response);
    }

    /**
     * Factory method
     */
    abstract public function createResponse(): ResponseInterface;

    /**
     * Gets the Template Renderer (aka View) for this controller
     */
    public function getTemplateRenderer(): TemplateRendererInterface
    {
        return $this->view;
    }

     /**
     * Before render hook
     */
    protected function beforeRender(): ?ResponseInterface
    {
        return null;
    }

    /**
     * After render hook
     */
    protected function afterRender(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    /**
     * Before Redirect hook
     */
    protected function beforeRedirect(string $url): ?ResponseInterface
    {
        return null;
    }

    /**
     * After Redirect hook
     */
    protected function afterRedirect(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
