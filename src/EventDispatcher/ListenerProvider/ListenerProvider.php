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

namespace Lightning\EventDispatcher\ListenerProvider;

use Psr\EventDispatcher\ListenerProviderInterface;

final class ListenerProvider implements ListenerProviderInterface
{
    private array $listeners = [];

    /**
     * Adds a listener
     */
    public function add(string $eventName, callable $callable): static
    {
        $this->listeners[$eventName][] = $callable;

        return $this;
    }

    /**
     * Removes a listener
     */
    public function remove(string $eventName, callable $callable): static
    {
        foreach ($this->listeners[$eventName] ?? [] as $index => &$handler) {
            if ($handler == $callable) {
                unset($this->listeners[$eventName][$index]);

                break;
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
