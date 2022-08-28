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

namespace Lightning\TestSuite;

interface TestSessionInterface
{
    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function clear(): void;
}
