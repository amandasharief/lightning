<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2023 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Console;

class InputStream
{
    private $resource;

    /**
     * Constructor
     */
    public function __construct(string $handle = 'php://stdin')
    {
        $this->resource = fopen($handle, 'r');
    }

    /**
     * Reads from the INPUT
     */
    public function read(?int $bytes = null): ?string
    {
        $data = $bytes ? fread($this->resource, $bytes) : fgets($this->resource);

        return $data ? trim($data) : null;
    }

    /**
     * Shutdown
     */
    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
