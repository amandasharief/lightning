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
 * AbstractListener - This class uses the __invoke method to call the `handle` method that you
 * will create in the class that overides this class. Since function method decalartions can be 
 * overridden therefore cant get code completion benefits, there is no abstract handle method. s
 *
 * Goals: have own listener class without having to call PHP magic method, typehinting in main method 
 * and works with other dispatchers. Can't overload interfaces or methods with the correct object 
 * declaration therefore this is a kind of adapter. 
 */
abstract class AbstractListener
{    
    /**
     * Invoke the listener by calling the handle
     */
    public function __invoke(object $event): void
    {
        call_user_func([$this, 'handle'], $event);
    }
}
