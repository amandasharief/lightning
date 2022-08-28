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

/**
 * ListenerRegistry
 */
class ListenerRegistry implements ListenerRegistryInterface
{
    protected array $listeners = [];

    /**
     * Registers a Listener for an event type
     */
    public function registerListener(string $eventType, callable $callable): static
    {
        $this->listeners[$eventType][] = $callable;

        return $this;
    }

    /**
     * Deteaches an even handler
     */
    public function unregisterListener(string $eventType, callable $callable): static
    {
        foreach ($this->listeners[$eventType] ?? [] as $index => $handler) {
            if ($handler == $callable) {
                unset($this->listeners[$eventType][$index]);
            }
        }

        return $this;
    }

    /**
     * Gets the Listeners for an Event
     */
    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners[$event::class] ?? [];
    }
}
