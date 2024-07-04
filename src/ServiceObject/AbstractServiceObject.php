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

namespace Lightning\ServiceObject;

use Lightning\Arguments\Arguments;

/**
 * Service Object
 *
 * Command Pattern: "an object is used to encapsulate all information needed to perform an action or trigger an event"
 */
abstract class AbstractServiceObject
{
    /**
     * A hook that is called before the execute method
     */
    protected function initialize(): void
    {
    }

    /**
     * The Service Object logic that will be executed when it is run
     */
    abstract protected function execute(Arguments $args): Result;

    /**
     * Runs the Service Object
     */
    public function dispatch(array $arguments): Result
    {
        $this->initialize();

        return $this->execute(new Arguments($arguments));
    }
}
