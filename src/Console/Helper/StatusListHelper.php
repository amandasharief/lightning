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
use InvalidArgumentException;
use Lightning\Console\Console;

/**
 * StatusList
 *
 * A check list kind of things for statuses, the end goal is for all to be green [  OK  ].
 * Use this for checking preinstallation, running services etc.
 */
class StatusListHelper
{
    protected array $statuses = [
        'ok' => ANSI::FG_GREEN,
    ];

    private int $maxWidth ;
    private bool $ansi = true;

    /**
     * Constructor
     */
    public function __construct(private Console $console, private bool $autopad = true)
    {
        $this->ansi = $console->stdout->isatty() && ! getenv('NO_COLOR');
    }

    /**
     * Sets a status
     */
    public function setStatus(string $name, array $ansiEscapeSequences): static
    {
        $this->statuses[strtolower($name)] = implode('', $ansiEscapeSequences);

        return $this;
    }

    public function setAutoPad(bool $enabled): static
    {
        $this->autopad = $enabled;

        return $this;
    }

    /**
     * Displays a status [ OK ] Something
     * @todo not sure on naming on this
     */
    public function status(string $status, string $message): static
    {
        if (! isset($this->statuses[$status])) {
            throw new InvalidArgumentException(sprintf('Unkown status `%s`', $status));
        }

        $stat = strtoupper($status);
        if ($this->autopad) {
            if (! isset($this->maxWidth)) {
                $this->maxWidth = max(array_map('strlen', array_keys($this->statuses)));
                if (! $this->maxWidth % 2) {
                    $this->maxWidth ++;
                }
            }
            $stat = str_pad(strtoupper($status), $this->maxWidth, ' ', STR_PAD_BOTH);
        }

        if ($this->ansi) {
            $this->console->out(sprintf('[%s %s %s] %s', $this->statuses[$status], $stat, ANSI::RESET, $message));
        } else {
            $this->console->out(sprintf('[ %s ] %s', $stat, $message));
        }

        return $this;
    }
}
