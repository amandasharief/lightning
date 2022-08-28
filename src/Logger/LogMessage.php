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

namespace Lightning\Logger;

use Stringable;

class LogMessage implements Stringable
{
    public function __construct(
        public string $message,
        public array $context = [],
    ) {
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    public function toString(): string
    {
        $replace = [];
        foreach ($this->context as $key => $value) {
            if (! is_array($value) && (! is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($this->message, $replace);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
