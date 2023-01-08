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

class ProgressBarHelper
{
    private string $completeStyle = ANSI::FG_BLUE . ANSI::BOLD;
    private string $incompleteStyle = ANSI::FG_WHITE;
    private string $barComplete = '█';
    private string $barEmpty = '░';
    private int $value = 0 ;

    private $isatty = true;
    private $color = true;

    /**
     * Constructor
     */
    public function __construct(private Console $console, private int $minimum = 0, private int $maximum = 100, private int $step = 1)
    {
        $this->isatty = $this->console->stdout->isatty();
        $this->color = $this->isatty && ! getenv('NO_COLOR');;

        if ($this->color) {
            $this->barEmpty = '█'; // Give modern look
        }
    }

    /**
     * Sets the style for the completed part of the progress bar
     */
    public function setStyle(array $ansiEscapeSequences): static
    {
        $this->completeStyle = implode('', $ansiEscapeSequences);

        return $this;
    }

    /**
     * Gets the style for the completed part of the progress bar
     */
    public function getStyle(): string
    {
        return $this->completeStyle;
    }

    /**
     * Gets the bar character for the completed part of the progress bar
     */
    public function getBarCharacter(): string
    {
        return $this->barComplete;
    }

    /**
     * Sets the bar character for the completed part of the progress bar
     */
    public function setBarCharacter(string $char): static
    {
        $this->barComplete = $char;

        return $this;
    }

    /**
     * Sets the style for the empty part of the progress bar
     */
    public function setEmptyStyle(array $ansiEscapeSequences): static
    {
        $this->incompleteStyle = implode('', $ansiEscapeSequences);

        return $this;
    }

    /**
     * Gets the style for the empty part of the progress bar
     */
    public function getEmptyStyle(): string
    {
        return $this->incompleteStyle;
    }

    /**
     * Sets the bar character for the empty part of the progress bar
     */
    public function setEmptyBarCharacter(string $char): static
    {
        $this->barEmpty = $char;

        return $this;
    }

     /**
     * Get the bar character for the empty part of the progress bar
     */
    public function getEmptyBarCharacter(): string
    {
        return $this->barEmpty ;
    }

    /**
     * Sets the value of the progress bar, and render it.
     */
    public function setValue(int $value): static
    {
        if ($value >= $this->minimum && $value <= $this->maximum) {
            $this->value = $value;

            // only animate if its a tty
            if ($this->isatty || $value === $this->maximum) {
                $this->draw($value, $this->maximum);
            }
        }

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Starts the progress bar from 0
     */
    public function start(int $value = 0, int $max = null): static
    {
        if ($max) {
            $this->maximum = $max;
        }

        return $this->setValue($value);
    }

    /**
     * Increments the progress bar by the value
     */
    public function increment(?int $value = 1): void
    {
        $this->setValue($this->value + $value);
    }

    /**
     * Completes the progress bar by rendering (if required) and goes to the next line
     */
    public function complete(): static
    {
        if ($this->value !== $this->maximum) {
            $this->setValue($this->maximum);
        }

        $this->console->out('');

        return $this;
    }

     /**
     * Draws a progress bar.
     * @see http://ascii-table.com/ansi-escape-sequences-vt-100.php
     */
    protected function draw(int $value, int $max): void
    {
        $percentage = floor(($value * 100) / $max);
        $pending = 100 - $percentage;
        if ($pending % 2 !== 0) {
            $pending ++;
        }

        // build
        $percentageString = str_pad((string) $percentage . '%', 4, ' ', STR_PAD_LEFT);
        $pbDone = str_repeat($this->barComplete, (int) floor($percentage / 2));
        $pbPending = str_repeat($this->barEmpty, (int) floor($pending / 2));

        if ($this->color) {
            $percentageString = $this->completeStyle . $percentageString . ANSI::RESET;
            $progressBar = $this->completeStyle. $pbDone . ANSI::RESET . $this->incompleteStyle . $pbPending . ANSI::RESET;
        } else {
            $percentageString = sprintf('[ %s ]', $percentageString);
            $progressBar = $pbDone . $pbPending;
        }

        $this->console->stdout->write(sprintf("\r %s %s", $progressBar, $percentageString));
    }

    /**
     * Get the value of minimum
     */
    public function getMinimum(): int
    {
        return $this->minimum;
    }

    /**
     * Set the value of minimum
     */
    public function setMinimum(int $minimum): static
    {
        $this->minimum = $minimum;

        return $this;
    }

    /**
     * Get the value of maximum
     */
    public function getMaximum(): int
    {
        return $this->maximum;
    }

    /**
     * Set the value of maximum
     */
    public function setMaximum(int $maximum): static
    {
        $this->maximum = $maximum;

        return $this;
    }
}
