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
 * OutputFormatter
 * @internal Formatters should be independant of Console
 */
class OutputFormatter
{
    protected bool $ansi = true;

    /**
     * Enables ANSI output in this formatter
     * @todo Think of better name
     */
    public function enableAnsi(bool $ansi): static
    {
        $this->ansi = $ansi;

        return $this;
    }

    public function isAnsiEnabled(): bool
    {
        return $this->ansi;
    }

  /**
     * Formats a string
     */
    public function format(string $message, array $context = []): string
    {
        if (! $this->ansi) {
            $message = $this->stripAnsiEscapeSequences($message);
        }

        return $context ? $this->interpolate($message, $context) : $message;
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
