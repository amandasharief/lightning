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
 * AbstractListener
 *
 * Goals: have own listener class without having to call PHP magic method, typehinting in main method and works with other dispatchers.
 * Can't overload interfaces or methods with the correct object declaration therefore a kind of adapter.
 */
abstract class AbstractListener
{
    /**
     * Invoke the listener
     */
    public function __invoke(object $event): void
    {
        call_user_func([$this, 'handle'], $event);
    }
}
