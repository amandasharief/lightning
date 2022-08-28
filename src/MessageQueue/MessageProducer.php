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

class MessageProducer
{
    /**
     * Constructor
     */
    public function __construct(protected MessageQueueInterface $messageQueue)
    {
    }

    /**
     * Gets the Message Queue for this Producer
     */
    public function getMessageQueue(): MessageQueueInterface
    {
        return $this->messageQueue;
    }

    /**
     * Sets the Message Queue for this Producer
     */
    public function setMessageQueue(MessageQueueInterface $messageQueue): static
    {
        $this->messageQueue = $messageQueue;

        return $this;
    }

    /**
     * Returns a new instance with a specific destination
     */
    public function send(string $destination, object $message, int $delay = 0): bool
    {
        return $this->messageQueue->send(
            $destination, serialize(new Message($message)), $delay
        );
    }
}
