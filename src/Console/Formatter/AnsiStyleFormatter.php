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

namespace Lightning\Console\Formatter;

use Lightning\Console\ANSI;

/**
 * ANSI Style Formatter formats strings with ANSI styles, if the formatter
 */
class AnsiStyleFormatter extends AnsiFormatter
{
    /**
    * Styles for this formatter
    */
    protected array $styles = [
        'red' => [ANSI::FG_RED],
        'green' => [ANSI::FG_GREEN],
        'yellow' => [ANSI::FG_YELLOW],
        'blue' => [ANSI::FG_BLUE],
        'magenta' => [ANSI::FG_MAGENTA],
        'cyan' => [ANSI::FG_CYAN],
        'white' => [ANSI::FG_WHITE]
    ];

    /**
     * Sets a style
     */
    public function setStyle(string $name, array $escapeSquences): static
    {
        $this->styles[$name] = $escapeSquences;

        return $this;
    }

    /**
     * Gets a style
     */
    public function getStyle(string $name): ?array
    {
        return $this->styles[$name] ?? null;
    }

    /**
     * Gets styles
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Formats a string
     */
    public function format(string $message, array $context = []): string
    {
        if ($context) {
            $message = $this->interpolate($message, $context);
        }

        if (! $this->terminalSupportsAnsi) {
            $tags = array_keys($this->styles);

            return preg_replace('/<\/?(' . implode('|', $tags) . ')>/', '', $message); // remove tags
        }

        return $this->convertTags($message);
    }

    /**
     * Convert Tags
     */
    private function convertTags(string $text): string
    {
        // Match style tags
        if (preg_match_all('/<([a-z0-9_]+)>(.*?)<\/(\1)>/ims', $text, $matches)) {
            foreach ($matches[1] as $key => $tag) {
                $style = $this->styles[$tag] ?? null;
                if ($style) {
                    $string = $matches[2][$key];
                    $text = str_replace($matches[0][$key], implode(' ', $style) . $string . ANSI::RESET, $text);;
                }
            }
        }

        return $text;
    }
}
