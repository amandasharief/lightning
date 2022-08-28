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

namespace Lightning\Dotenv;

/**
 * Gets a value from the environment
 *
 * @param string $key
 * @param string|null $default
 * @return string|null
 */
function env(string $key, ?string $default = null): string|null
{
    $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

    if ($value === null) {
        $value = getenv($key) ?: null;
    }

    return $value ?: $default;
}
