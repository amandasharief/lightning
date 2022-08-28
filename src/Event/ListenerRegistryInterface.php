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

namespace Lightning\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * ListenerRegistryInterface
 *
 * @internal deal with the problem that the PSR does include a standard way to register listeners.
 */
interface ListenerRegistryInterface extends ListenerProviderInterface
{
    /**
     * Registers a Listener for an event type
     */
    public function registerListener(string $eventType, callable $callable): static;

    /**
     * Unregisters a Listener for an event type
     */
    public function unregisterListener(string $eventType, callable $callable): static;
}
