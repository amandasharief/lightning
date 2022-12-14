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

namespace Lightning\Http\Auth;

use Stringable;
use JsonSerializable;

class Identity implements Stringable, JsonSerializable
{
    private array $data;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Sets the Data
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns a new instance with this data
     *
     * @param array $data
     * @return static
     */
    public function withData(array $data): static
    {
        return (clone $this)->setData($data);
    }

    /**
     * Gets the data for this result or data from a specific property
     *
     * @param string|null $property
     * @return mixed
     */
    public function get(string $property): mixed
    {
        return $this->data[$property] ?? null;
    }

    /**
     * Gets the credentials as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Gets the credentials as a string
     *
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->data);
    }

    /**
     * PHP Stringable interface
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->data);
    }

    /**
     * Returns the data to be serialized to JSON
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    /**
     * Get the value of data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
