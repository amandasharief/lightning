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

namespace Lightning\Console;

use RuntimeException;
use Lightning\Console\Stream\InputStream;
use Lightning\Console\Stream\OutputStream;

/**
 * Console
 *
 * @internal stdout, stderr, stdin intenionally left as public properties. Keeping this object to the minium, output levels etc kept seperate
 * me think. By default sending output should work similar to console.log in JS (additional arguments are treated as an sprintf string).
 */
class Console
{
    public OutputStream $stdout;
    public OutputStream $stderr;
    public InputStream $stdin;
    public const LF = PHP_EOL;

    /**
     * Constructor
     */
    public function __construct(?OutputStream $stdout = null, ?OutputStream $stderr = null, ?InputStream $stdin = null)
    {
        $this->stdout = $stdout ?: new OutputStream('php://stdout');
        $this->stderr = $stderr ?: new OutputStream('php://stderr');
        $this->stdin = $stdin ?: new InputStream('php://stdin');
    }

    /**
     * Writes a formatted string to the "standard" output stream with a newline added
     */
    public function out(string $message, mixed ...$args): static
    {
        $this->stdout->write(($args ? sprintf($message, ...$args) : $message) . static::LF);

        return $this;
    }

    /**
     * Writes a formatted string to the "standard" error output stream with a newline added
     */
    public function error(string $message, mixed ...$args): static
    {
        $this->stderr->write(($args ? sprintf($message, ...$args) : $message) . static::LF);

        return $this;
    }

    /**
     * Reads a line from the "standard" input stream with a space added
     */
    public function readLine(?string $message = null, mixed ...$args): ?string
    {
        if (! $this->stdin->isatty()) {
            throw new RuntimeException('Trying to get input on a non terminal device');
        }

        if ($message) {
            $this->stdout->write(($args ? sprintf($message, ...$args) : $message . ' '));
        }

        return $this->stdin->read();
    }

    /**
     * Reads a line from the "standard" input stream with a space added and echoing disabled
     */
    public function readPassword(?string $message = null, mixed ...$args): ?string
    {
        if (! $this->stdin->isatty()) {
            throw new RuntimeException('Trying to get input on a non terminal device');
        }

        if ($message) {
            $this->stdout->write(($args ? sprintf($message, ...$args) : $message) . ' ');
        }

        shell_exec('stty -echo');
        $result = $this->stdin->read();
        shell_exec('stty echo');

        return $result;
    }
}
