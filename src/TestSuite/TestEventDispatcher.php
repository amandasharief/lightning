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

namespace Lightning\TestSuite;

use Countable;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * EventDispatcher
 */
class TestEventDispatcher implements EventDispatcherInterface, Countable
{
    private EventDispatcherInterface $eventDispatcher;
    private array $dispatchedEvents = [];

    /**
     * Constructor
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Forward any dispatcher methods calls to the real dispatcher
     */
    public function __call(string $method, array $args): mixed
    {
        $result = call_user_func_array([$this->eventDispatcher,$method], $args);

        return $result instanceof EventDispatcherInterface ? $this : $result;
    }

    /**
     * Dispatches the event
     */
    public function dispatch(object $event): object
    {
        return $this->dispatchedEvents[] = $this->eventDispatcher->dispatch($event) ;
    }

    /**
     * Gets the dispatched events
     * @return string[]
     */
    public function getDispatchedEvents(): array
    {
        return array_map(function (object $event) {
            return $event::class;
        }, $this->dispatchedEvents);
    }

    /**
     * Checks if an event was dispatched (using class name).
     * @internal some implementations have interface for getting name, this does not and will not
     * check those
     */
    public function hasDispatchedEvent(string $class): bool
    {
        foreach ($this->dispatchedEvents as $event) {
            if ($this->getEventName($event) === $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the DispatchedEvent by class name
     */
    public function getDispatchedEvent(string $class): ?object
    {
        foreach ($this->dispatchedEvents as $event) {
            if ($this->getEventName($event) === $class) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Override this method if you are using generic events
     */
    public function getEventName(object $event): string
    {
        return $event::class;
    }

    /**
     * Countable
     */
    public function count(): int
    {
        return count($this->dispatchedEvents);
    }
}
