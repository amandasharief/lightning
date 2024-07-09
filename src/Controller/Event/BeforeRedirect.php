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

namespace Lightning\Controller\Event;

use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\AbstractController;

/**
 * Before Redirect Event
 */
final class BeforeRedirect extends AbstractControllerEvent
{
    public function __construct(
        protected AbstractController $controller, 
        protected ?ResponseInterface $response = null,
        protected string $uri
    )
    {

    }

    public function getUri(): string
    {
        return $this->uri;
    }
}