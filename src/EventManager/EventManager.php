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

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Event Manager
 */
class EventManager implements EventManagerInterface, EventDispatcherInterface
{
    private array $listeners = [];

    /**
     * Adds a Listener
     */
    public function addListener(string $eventName, callable $callable): static
    {
        $this->listeners[$eventName][] = $callable;

        return $this;
    }

    /**
     * Removes a Listener from the listener collection
     */
    public function removeListener(string $eventName, callable $callable): static
    {
        foreach ($this->listeners[$eventName] ?? [] as $index => &$listener) {
            if ($listener == $callable) {
                unset($this->listeners[$eventName][$index]);
                // clean up properly here
                if (empty($this->listeners[$eventName])) {
                    unset($this->listeners[$eventName]);
                }

                break;
            }
        }

        return $this;
    }

    /**
     * Checks if an event has listeners
     *
     * @internal keep this function super lean
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]);
    }

    /**
     * Dispatches an Event
     */
    public function dispatch(object $event): object
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
