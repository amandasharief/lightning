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

namespace Lightning\MessageQueue;

interface MessageQueueInterface
{
    /**
     * Sends a message to the message queue
     */
    public function send(string $queue, string $message, int $delay = 0): bool;

    /**
     * Receives the next message from the queue, if any
     */
    public function receive(string $queue): ?string;
}
