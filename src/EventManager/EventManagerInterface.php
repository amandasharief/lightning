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

namespace Lightning\EventManager;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Event Manager Interface
 *
 * @internal Create bridge between app, listenerprovider and dispatcher and help optimise the dispatch process
 */
interface EventManagerInterface extends EventDispatcherInterface
{
    /**
     * Adds a Listener
     *
     * When implementing this interface, if you require an additional parameter such as
     * priority or options, you can define this as an optional parameter in your EventManager
     */
    public function addListener(string $eventName, callable $listener): static;

    /**
     * Removes a Listener
     */
    public function removeListener(string $eventName, callable $listener): static;

    /**
     * Checks if there are Listners for an event name
     */
    public function hasListeners(string $eventName): bool;
}
