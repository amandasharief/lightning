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

/**
 * AbstractStoppableEvent
 */
abstract class AbstractStoppableEvent extends AbstractEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    /**
     * Is propagation stopped?
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stop the event propagation
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
