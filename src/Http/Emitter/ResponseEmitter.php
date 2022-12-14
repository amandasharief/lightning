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

namespace Lightning\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

class ResponseEmitter
{
    /**
     * Emits a response
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        $filename = null;
        $line = 0;

        if (headers_sent($filename, $line)) {
            trigger_error(sprintf('Headers were already sent in %s on line %s', $filename, $line), E_USER_WARNING);
        }

        $this->sendHeader(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()));

        $cookies = [];
        foreach ($response->getHeaders() as $key => $value) {
            if (strtolower($key) === 'set-cookie') {
                $cookies = $value;

                continue;
            }
            $this->sendHeader(sprintf('%s: %s', $key, $response->getHeaderLine($key)));
        }

        foreach ($cookies as $cookie) {
            $this->sendHeader(sprintf('Set-Cookie: %s', $cookie), false);
        }

        // ignore no content or not modified response
        if (! in_array($response->getStatusCode(), [204,304])) {
            echo (string) $response->getBody();
        }

        $this->exit();
    }

    /**
     * Sends a header
     * @codeCoverageIgnore
     * @param string $header
     * @param boolean $replace
     * @return void
     */
    protected function sendHeader(string $header, bool $replace = true): void
    {
        header($header, $replace);
    }

    /**
     * Exit
     * @codeCoverageIgnore
     * @return void
     */
    protected function exit(): void
    {
        exit();
    }
}
