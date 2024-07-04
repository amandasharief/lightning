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

namespace Lightning\Arguments;

use Lightning\Arguments\Exception\UnkownArgumentException;

class Arguments
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
     * Set a value of an argument
     */
    public function set(string $name, mixed $value): static
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Gets a param
     *
     * @throws \Lightning\Arguments\Exception\UnknownArgumentException
     */
    public function get(string $name): mixed
    {
        if (! array_key_exists($name,$this->data)) {
            throw new UnkownArgumentException(sprintf('Unkown argument `%s`', $name));
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
