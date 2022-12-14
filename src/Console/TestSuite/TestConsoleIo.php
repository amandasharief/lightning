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

namespace Lightning\Console\TestSuite;

use RuntimeException;
use Lightning\Console\ConsoleIo;

final class TestConsoleIo extends ConsoleIo
{
    protected string $stdoutOutput = '';
    protected string $stderrOutput = '';
    protected array $stdinInput = [];

    private int $current = -1;

    /**
     * Constructor
     *
     * Change default to RAW
     */
    public function __construct()
    {
        parent::__construct(); // hie o

        $this->outputMode = self::RAW;
    }

    protected function writeStderr(string $data): void
    {
        $this->stderrOutput .= $data;
    }

    protected function writeStdout(string $data): void
    {
        $this->stdoutOutput .= $data;
    }

    protected function readStdin(): string
    {
        $this->current ++;

        if (! isset($this->stdinInput[$this->current])) {
            throw new RuntimeException('Console input is requesting more input that what was provided');
        }

        return $this->stdinInput[$this->current];
    }

    public function getStdout(): string
    {
        return $this->stdoutOutput;
    }

    public function getStderr(): string
    {
        return $this->stderrOutput;
    }

    /**
     * Sets the input
     */
    public function setInput(array $input): static
    {
        $this->stdinInput = $input;

        return $this;
    }

    /**
     * Resets the IO object
     */
    public function reset(): void
    {
        $this->stdoutOutput = '';
        $this->stderrOutput = '';
        $this->stdinInput = [];
    }
}
