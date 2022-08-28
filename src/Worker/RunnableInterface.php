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

namespace Lightning\Worker;

/**
 * RunnableInterface
 *
 * @internal this does not return a result, it should throw an exception incase of an error
 */
interface RunnableInterface
{
    public function run(): void;
}
