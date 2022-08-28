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
 * EventDispatcherAwareTrait
 *
 * This covers our implemenation
 */
trait EventDispatcherAwareTrait
{
    protected EventDispatcher $eventDispatcher;

    /**
     * Gets the Event Dispatcher
     */
    public function getEventDispatcher(): ?EventDispatcher
    {
        return $this->eventDispatcher ?? null;
    }

    /**
     * Sets the Event Dispatcher
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher): static
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
