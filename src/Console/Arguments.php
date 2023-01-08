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

class Arguments
{
    protected array $arguments;
    protected array $options;

    /**
     * Constructor
     *
     * @param array $options
     * @param array $arguments
     */
    public function __construct(array $options = [], array $arguments = [])
    {
        $this->options = $options;
        $this->arguments = $arguments;
    }

    /**
     * Get the value of arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set the value of arguments
     */
    public function setArguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Get the value of options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the value of options
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Gets an option that was provided to the command line script
     */
    public function getOption(string $option, mixed $default = null): mixed
    {
        return $this->options[$option] ?? $default;
    }

    /**
     * Gets an argument that was provided to the command line script
     */
    public function getArgument(string $argument, mixed $default = null): mixed
    {
        return $this->arguments[$argument] ?? $default;
    }

    /**
     * Checks if an option is defined and not null
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Checks if an argument exists
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }
}
