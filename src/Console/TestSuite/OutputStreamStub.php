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

namespace Lightning\Console\TestSuite;

use Lightning\Console\Stream\OutputStream;

class OutputStreamStub extends OutputStream
{
    private string $output = '';

    /**
     * Writes to the output to the stream and returns bytes (0 for false)
     */
    public function write(string $output): int
    {
        $this->output .= $output;

        return mb_strlen($output); /** @todo does this work */
    }

    /**
     * Gets the output of this stream
     */
    public function getContents(): string
    {
        return $this->output;
    }

    /**
     * Is this stream an interactive terminal (a TTY)
     */
    public function isatty(): bool
    {
        return true;
    }
}
