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

namespace Lightning\Validator;

use Countable;

class Errors implements Countable
{
    private array $errors = [];

    /**
     * Get the value of errors
     */
    public function getErrors(?string $field = null): array
    {
        return $field ? ($this->errors[$field] ?? []) : $this->errors;
    }

    /**
     * Gets the first error message for a field if exists
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Set the value of all errors
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Sets an error message
     */
    public function setError(string $field, string $message): static
    {
        $this->errors[$field][] = $message;

        return $this;
    }

    /**
     * Checks if there are errors
     */
    public function hasErrors(?string $field = null): bool
    {
        return $field ? ! empty($this->errors[$field]) : ! empty($this->errors);
    }

    /**
     * Resets the errors
     */
    public function reset(): static
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Gets the errors count
     */
    public function getErrorsCount(?string $field): int
    {
        return $field ? count($this->getErrors($field)) : $this->count();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->errors as $field => $errors) {
            $count += count($errors);
        }

        return $count;
    }
}
