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

namespace Lightning\Params;

use Lightning\Params\Exception\UnknownParameterException;

class Params
{
    /**
     * Container data
     */
    protected array $data = [];

    /**
     * Constructor
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Set a value of a param
     */
    public function set(string $name, mixed $value): static
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Gets a param
     *
     * @throws \Lightning\ServiceObject\Exception\UnknownParameterException
     */
    public function get(string $name): mixed
    {
        if (! isset($this->data[$name])) {
            throw new UnknownParameterException(sprintf('Unkown parameter `%s`', $name));
        }

        return $this->data[$name];
    }

    /**
     * Checks if a parameter exists
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Unset a param
     */
    public function unset(string $name): static
    {
        unset($this->data[$name]);

        return $this;
    }

    /**
     * Gets the params as an array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
