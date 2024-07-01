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

use RuntimeException;
use Lightning\Console\Stream\InputStream;

class InputStreamStub extends InputStream
{
    private array $input = [];
    private int $current = -1;

    public function read(?int $bytes = null): ?string
    {
        $this->current ++;

        if (! isset($this->input[$this->current])) {
            throw new RuntimeException('Console input is requesting more input that what was provided');
        }

        return $bytes ? mb_substr($this->input[$this->current], $bytes) : $this->input[$this->current];
    }

    public function setInput(array $input)
    {
        $this->input = $input;
    }

    /**
     * Is this stream an interactive terminal (a TTY)
     */
    public function isatty(): bool
    {
        return true;
    }
}
