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

class MemoryMessageQueue implements MessageQueueInterface
{
    protected array $messages = [];

    /**
     * Sends a message to the message queue
     */
    public function send(string $queue, string $message, int $delay = 0): bool
    {
        if (! isset($this->messages[$queue])) {
            $this->messages[$queue] = [];
        }

        $this->messages[$queue][] = [$message,time() + $delay];

        return true;
    }

    /**
     * Receives the next message from the queue, if any
     */
    public function receive(string $queue): ?string
    {
        foreach ($this->messages[$queue] ?? [] as $index => $data) {
            list($message, $when) = $data;
            if ($when <= time()) {
                unset($this->messages[$queue][$index]);

                return $message;
            }
        }

        return null;
    }
}
