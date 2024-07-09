<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Controller\Event;

use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\AbstractController;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractControllerEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        protected AbstractController $controller, 
        protected ?ResponseInterface $response = null
    )
    {
    }

    public function getController(): AbstractController
    {
        return $this->controller;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
