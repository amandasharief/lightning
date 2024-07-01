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

namespace Lightning\Console\Stream;

use InvalidArgumentException;

class OutputStream
{
    private $resource;

    /**
     * Constructor
     */
    public function __construct(string $handle, string $mode = 'w')
    {
        $this->resource = fopen($handle, $mode);
        if (! $this->resource) {
            throw new InvalidArgumentException(sprintf('Error opening `%s`', $handle));
        }
    }

    /**
     * Writes to the output to the stream and returns bytes (0 for false)
     */
    public function write(string $output): int
    {
        return (int) fwrite($this->resource, $output);
    }

    /**
     * Gets the stream
     */
    public function getStream()
    {
        return $this->resource;
    }

    /**
     * Is this stream an interactive terminal (a TTY)
     */
    public function isatty(): bool
    {
        return $this->resource && stream_isatty($this->resource);
    }

    /**
     * Closes the stream
     */
    public function close(): bool
    {
        return fclose($this->resource);
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
