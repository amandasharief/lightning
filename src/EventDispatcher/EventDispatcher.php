<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 20224 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\EventDispatcher;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 Event Dispatcher
 *
 * @internal There should be no SETTER for listenerProvider.
 */
final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(private ListenerProviderInterface $listenerProvider)
    {
    }

    /**
     * Gets the Listener Provider that the Dispatcher is Using
     */
    public function getListenerProvider(): ListenerProviderInterface
    {
        return $this->listenerProvider;
    }

    /**
     * Use this method to configure Listener providers on objects are using the dispatcher.
     *
     * This is my solution to be able to register listeners from code that wishes to emit an event e.g. controller without having
     * to pass the listener provider, or not have code suggestion due to ListenerProviderInterface. As simple as this looks its
     * taken me a very long time to come up with a global solution that works with the PSR-14 design
     */
    public function configure(callable $callable): static
    {
        $callable($this->listenerProvider);

        return $this;
    }

    /**
     * Dispatches an Event
     */
    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
