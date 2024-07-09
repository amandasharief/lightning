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

namespace Lightning\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventDispatcherAwareInterface
{
    /**
     * Gets the Event Dispatcher
     */
    public function getEventDispatcher(): EventDispatcherInterface;

    /**
     * Sets the Event Dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): static;

    /**
     * Dispatches an Event through the Event Dispatcher and returns the Event
     */
    public function dispatchEvent(object $event): object;
}
