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

class OutputStream
{
    private $resource;

    /**
     * Constructor
     */
    public function __construct(string $handle = 'php://stdout')
    {
        $this->resource = fopen($handle, 'w');
    }

    /**
     * Writes to the output to the stream and returns bytes (0 for false)
     */
    public function write(string $output): int
    {
        return (int) fwrite($this->resource, $output);
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
