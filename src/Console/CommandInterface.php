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

namespace Lightning\Console;

interface CommandInterface
{
    /**
     * Gets the name of the command
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the description of the command
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Runs the command
     *
     * @param array $args
     * @return integer
     */
    public function run(array $args): int ;
}
