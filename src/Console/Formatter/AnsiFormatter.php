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

namespace Lightning\Console\Formatter;

/**
 * AnsiFormatter provides interpolation of strings and if no ansi mode is enabled then escape sequences are stripped automatically.
 */
class AnsiFormatter
{
    protected bool $terminalSupportsAnsi = true;

    /**
     * Format
     */
    public function format(string $message, array $context = []): string
    {
        if (! $this->terminalSupportsAnsi) {
            $message = $this->stripAnsiEscapeSequences($message);
        }

        return $context ? $this->interpolate($message, $context) : $message;
    }

    /**
     * Disables ANSI output
     */
    public function noAnsi(): static
    {
        $this->terminalSupportsAnsi = false;

        return $this;
    }

    /**
     * Interpolates context values into message place holders
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }

        return strtr($message, $replace);
    }

    /**
     * Strips ANSI escape sequences
     */
    protected function stripAnsiEscapeSequences(string $string): string
    {
        return preg_replace('#\033[[0-9;]+m#', '', $string);
    }
}
