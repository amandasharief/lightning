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

namespace Lightning\Formatter;

use Stringable;

class MessageFormatter
{
    /**
     * Formats a message
     *
     * @param string $message
     * @param array $values
     * @return string
     */
    public function format(string $message, array $values): string
    {
        if (strpos($message, '|') !== false && isset($values['count'])) {
            $messages = explode('|', $message);

            // use count number if set, if not use the last.
            $message = $messages[$values['count']] ?? array_pop($messages);
        }

        return $this->interpolate($message, $values);
    }

    /**
     * Interpolate values into the message placeholders.
     *
     * @param string $message
     * @param array $values
     * @return string
     */
    private function interpolate(string $message, array $values): string
    {
        $replace = [];
        foreach ($values as $key => $value) {
            if (is_scalar($value) || is_null($value) || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }
}
