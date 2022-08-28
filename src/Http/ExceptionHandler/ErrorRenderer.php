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

namespace Lightning\Http\ExceptionHandler;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;

class ErrorRenderer
{
    /**
    * Renders the HTML body
    *
    * @param string $template
    * @param string $message
    * @param integer $code
    * @param ServerRequestInterface $request
    * @param Throwable $exception
    * @return string
    */
    public function html(string $template, string $message, int $code, ServerRequestInterface $request, Throwable $exception): string
    {
        ob_start();
        require $template;

        return ob_get_clean();
    }

    /**
     * Renders the JSON body
     *
     * @param string $message
     * @param integer $code
     * @return string
     */
    public function json(string $message, int $code): string
    {
        return json_encode([
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
    }

    /**
     * Render the XML body
     *
     * @param string $message
     * @param integer $code
     * @return string
     */
    public function xml(string $message, int $code): string
    {
        return <<< XML
        <?xml version="1.0" encoding="UTF-8"?>
        <error>
           <code>{$code}</code>
           <message>{$message}</message>
        </error>
        XML;
    }
}
