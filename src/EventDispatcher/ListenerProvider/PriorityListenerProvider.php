<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\EventDispatcher\ListenerProvider;

use Psr\EventDispatcher\ListenerProviderInterface;

final class PriorityListenerProvider implements ListenerProviderInterface
{
    public const HIGH_PRIORITY = 100;
    public const NORMAL_PRIORITY = 0;
    public const LOW_PRIORITY = -100;

    private array $listeners = [];
    private array $sortedListeners = [];

    /**
     * Adds a listener
     */
    public function add(string $eventName, callable $callable, int $priority = 0): static
    {
        $this->listeners[$eventName][$priority][] = $callable;
        unset($this->sortedListeners[$eventName]);

        return $this;
    }

    /**
     * Removes a listener
     */
    public function remove(string $eventName, callable $callable): static
    {
        foreach ($this->listeners[$eventName] ?? [] as $priority => &$listeners) {
            foreach ($listeners as $index => &$handler) {
                if ($handler == $callable) {
                    unset($this->listeners[$eventName][$priority][$index], $this->sortedListeners[$eventName]);

                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Gets the Listeners for an Event
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventName = $event::class;

        // note: unregistering may leave set but empty
        if (empty($this->listeners[$eventName])) {
            return [];
        }

        if (! isset($this->sortedListeners[$eventName])) {
            $this->sortListeners($eventName);
        }

        return $this->sortedListeners[$eventName];
    }

    private function sortListeners(string $eventName): void
    {
        $this->sortedListeners[$eventName] = [];

        krsort($this->listeners[$eventName], SORT_NUMERIC);

        // IMPORTANT: pass by reference
        foreach ($this->listeners[$eventName] ?? [] as &$listeners) {
            foreach ($listeners as &$listener) {
                $this->sortedListeners[$eventName][] = $listener;
            }
        }
    }
}
