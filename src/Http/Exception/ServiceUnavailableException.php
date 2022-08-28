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

namespace Lightning\Http\Exception;

use Throwable;

class ServiceUnavailableException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message = 'Service Unavailable', ?Throwable $previous = null)
    {
        parent::__construct($message, 503, $previous);
    }
}
