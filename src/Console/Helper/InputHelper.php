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

use Lightning\Console\Console;

class InputHelper
{
    /**
     * Constructor
     */
    public function __construct(private Console $console)
    {
    }

    /**
     * Asks for input
     */
    public function ask(string $message, ?string $default = null): ?string
    {
        if ($default) {
            $message = sprintf('%s [%s]', $message, (string) $default);
        }

        return $this->console->in($message . Console::LF . '> ') ?: $default;
    }

    /**
     * Ask for input of something secret like a password or passphrase, echoing is disabled. NIX only
     */
    public function askSecret(string $message): ?string
    {
        $this->console->out($message . Console::LF . '> ');

        shell_exec('stty -echo');
        $result = $this->console->stdin->read();
        shell_exec('stty echo');

        return $result;
    }

    /**
     * Asks a question with available choices
     */
    public function askChoice(string $message, array $choices, ?string $default = null): string
    {
        $message = $message . ' (' . implode('/', $choices) . ')';
        $result = $this->ask($message, $default);

        while (! in_array($result, $choices)) {
            $result = $this->askChoice($message, $choices, $default);
        }

        return $result;
    }
}
