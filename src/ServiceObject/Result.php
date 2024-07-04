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

namespace Lightning\ServiceObject;

use Stringable;
use JsonSerializable;

/**
 * Result Object [imutable]
 */
class Result implements JsonSerializable, Stringable
{
    /**
     * Constructor
     */
    public function __construct(private bool $success, private array $data = [])
    {
    }

    /**
     * Is the a Result a success?
     */
    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    /**
     * Checks if the result has data
     */
    public function hasData(): bool
    {
        return ! empty($this->data);
    }

    /**
     * Gets the data for this result
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets the data for a specific key
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * Checks if the key exists in the data
     */
    public function has(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Returns the data to be serialized to JSON
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * PHP Stringable interface
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts this result to JSON
     */
    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts this result to an array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data
        ];
    }
}
