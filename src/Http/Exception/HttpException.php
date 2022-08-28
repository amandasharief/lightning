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

use Exception;
use Throwable;

/**
 * @see https://httpstatuses.com/
 */
class HttpException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message
     * @param integer $statusCode
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $statusCode, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
