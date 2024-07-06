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

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 Event Dispatcher
 * 
 * @internal decided to ditch the listener register, there is no common way to register unregister an
 * event therefore this means it is not possible to call a method on the dispatcher to register an
 * event therefore will always be reistricted to our own listener providers. at this stage I dont see
 * any need to overcomplicate things and add other reigstered listener types therefore is has been combined
 * into a single file
 */
class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    protected array $listeners = [];

    /**
     * Registers a Listener for an event type
     */
    public function addListener(string $eventName, callable $callable): static
    {
        $this->listeners[$eventName][] = $callable;

        return $this;
    }

    /**
     * Deteaches an even handler
     */
    public function removeListener(string $eventName, callable $callable): static
    {
        foreach ($this->listeners[$eventName] ?? [] as $index => $handler) {
            if ($handler == $callable) {
                unset($this->listeners[$eventName][$index]);
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

    /**
     * Dispatches an Event
     */
    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                return $event;
            }

            $listener($event);
        }

        return $event;
    }
}
