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

class InputStream
{
    private $resource;

    /**
     * Constructor
     */
    public function __construct(string $handle, string $mode = 'r')
    {
        $this->resource = fopen($handle, $mode);
        if (! $this->resource) {
            throw new InvalidArgumentException(sprintf('Error opening `%s`', $handle));
        }
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
     * Gets the stream
     */
    public function getStream()
    {
        return $this->resource;
    }

    /**
     * Is this stream an interactive terminal (a TTY)
     * @todo naming on  this yeah o neah?
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
