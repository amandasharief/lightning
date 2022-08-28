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

namespace Lightning\Worker;

interface RetryableInterface
{
    /**
     * Instructs the message object that processing failed
     */
    public function fail(): void;

    /**
     * Gets the number of attempts
     */
    public function attempts(): int ;

    /**
     * Gets the maximum number of times this message processing should be retried
     */
    public function maxRetries(): int;

    /**
     * Seconds to wait before retrying
     */
    public function delay(): int;
}
