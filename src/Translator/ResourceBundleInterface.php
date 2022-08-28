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

namespace Lightning\Translator;

interface ResourceBundleInterface
{
    /**
     * @throws ResourceNotFoundException if no entry for the key is found
     */
    public function get(string $key): string;

    /**
     * Checks if the resource bundle has an entry for the key
     */
    public function has(string $key): bool;
}
