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

namespace Lightning\Console\Helper;

use Lightning\Console\ANSI;
use Lightning\Console\Console;

class AlertHelper
{
    private bool $ansi = true;

    /**
     * Constructor
     */
    public function __construct(private Console $console)
    {
        $this->ansi = $console->stdout->isatty() && ! getenv('NO_COLOR');
    }

    private function render(string $text, ?string $secondary, array $ansiEscapeSequences): string
    {
        if ($this->ansi) {
            return implode('', $ansiEscapeSequences) . sprintf(' %s ', $text) .  ANSI::RESET  . ($secondary ? ' ' . $secondary : null);
        }

        return sprintf(' %s ', $text) . ($secondary ? ' ' . $secondary : null);
    }

    /**
     * Displays an info alert
     */
    public function info(string $text, ?string $secondary = null, array $ansiEscapeSequences = [ANSI::BG_BLUE, ANSI::FG_WHITE, ANSI::BOLD]): static
    {
        $this->console->out($this->render($text, $secondary, $ansiEscapeSequences));

        return $this;
    }

    /**
     * Displays a success alert
     */
    public function success(string $text, ?string $secondary = null, array $ansiEscapeSequences = [ANSI::BG_GREEN, ANSI::FG_WHITE, ANSI::BOLD]): static
    {
        $this->console->out($this->render($text, $secondary, $ansiEscapeSequences));

        return $this;
    }

    /**
         * Displays a warning alert (stderr)
        */
    public function warning(string $text, ?string $secondary = null, array $ansiEscapeSequences = [ANSI::BG_YELLOW, ANSI::FG_WHITE, ANSI::BOLD]): static
    {
        $this->console->error($this->render($text, $secondary, $ansiEscapeSequences));

        return $this;
    }

    /**
     * Displays an error alert (stderr)
     */
    public function error(string $text, ?string $secondary = null, array $ansiEscapeSequences = [ANSI::BG_RED, ANSI::FG_WHITE, ANSI::BOLD]): static
    {
        $this->console->error($this->render($text, $secondary, $ansiEscapeSequences));

        return $this;
    }
}
