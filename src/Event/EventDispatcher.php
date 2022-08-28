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

/**
 * PSR-14 Event Dispatcher
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Constructor
     */
    public function __construct(protected ListenerRegistryInterface $listenerRegistry)
    {
    }

    /**
     * Get the Listener Registry
     */
    public function getListenerRegistry(): ListenerRegistryInterface
    {
        return $this->listenerRegistry;
    }

    /**
     * Sets the ListenerRegistry
     */
    public function setListenerRegistry(ListenerRegistryInterface $listenerRegistry): static
    {
        $this->listenerRegistry = $listenerRegistry;

        return $this;
    }

    /**
     * Dispatches an Event
     */
    public function dispatch(object $event): object
    {
        foreach ($this->listenerRegistry->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                return $event;
            }

            $listener($event);
        }

        return $event;
    }
}
