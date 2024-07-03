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

use Psr\Http\Message\ResponseInterface;

interface ControllerLifecycleInterface
{
    /**
     * Hook is called when the Controller object is created
     */
    public function initialize(): void;

    /**
     * Before render hook
     */
    public function beforeRender(): ?ResponseInterface;

    /**
     * After render hook
     */
    public function afterRender(ResponseInterface $response): ResponseInterface;

    /**
     * Before Redirect hook
     */
    public function beforeRedirect(string $url): ?ResponseInterface;

    /**
     * After Redirect hook
     */
    public function afterRedirect(ResponseInterface $response): ResponseInterface;
}
