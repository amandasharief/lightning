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
 * AbstractEvent
 *
 * @internal this object reduces the repeated code, namely source but also added timestamp similar to Messages
 */
abstract class AbstractEvent
{
    private int $timestamp;

    /**
     * Constructor
     */
    public function __construct(private object $source)
    {
        $this->timestamp = time();
    }

    /**
     * Gets the object which the Event was triggered on
     */
    public function getSource(): object
    {
        return $this->source;
    }

    /**
     * Gets the timestamp this Event was created
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Gets a string representation of the Event
     */
    public function toString(): string
    {
        return serialize($this);
    }
}
