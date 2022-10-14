<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2022 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opentable.org/licenses/mit-license.php MIT License
 */

namespace Lightning\Database;

use Stringable;
use ArrayAccess;
use JsonSerializable;

class Row implements ArrayAccess, JsonSerializable, Stringable
{
    private array $data = [];
    
    final public function __construct()
    {
    }

    /**
     * Creates the Row object using the row from the database
     */
    public static function fromState(array $state): Row
    {
        $row = new static();
        $row->data = $state;

        return $row;
    }
    /**
     * Sets a value
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Sets a value
     */
    public function set(string $key, mixed $value = null): static
    {
        $this->$key = $value;

        return $this;
    }

    /**
     * Gets a value
     */
    public function &__get(string $key): mixed
    {
        $value = null;
        if (array_key_exists($key, $this->data)) {
            $value = &$this->data[$key];
        }

        return $value;
    }

    /**
     * Gets a value
     */
    public function get(string $key): mixed
    {
        return $this->__get($key);
    }

    /**
     * Checks if a property set
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Checks if this object has a property
     */
    public function has(string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Unsets a property
     */
    public function __unset(string $property)
    {
        unset($this->data[$property]);
    }

    /**
     * Unsets a property
     */
    public function unset(string $property): void
    {
        $this->__unset($property);
    }

    /**
     * JsonSerializable Interface
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Converts this object recrusively to an array (if other rows were later added)
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->data as $property => $value) {
            if (is_array($value)) {
                $result[$property] = [];
                foreach ($value as $k => $v) {
                    $result[$property][$k] = $v instanceof Row ? $v->toArray() : $v;
                }
            } else {
                $result[$property] = $value instanceof Row ? $value->toArray() : $value;
            }
        }

        return $result;
    }

    /**
     * Returns a string representation of this object
     */
    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Returns a string representation of this object
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * ArrayAcces Interface for isset($collection);
     *
     * @param mixed $key
     * @return bool result
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * ArrayAccess Interface for $collection[$key];
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * ArrayAccess Interface for $collection[$key] = $value;
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->data[] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * ArrayAccess Interface for unset($collection[$key]);
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->data[$key]);
    }
}
