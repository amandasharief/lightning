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

class MessageConsumer
{
    private $handler = null;
    protected bool $receiving = true;

    /**
     * Constructor
     */
    public function __construct(protected MessageQueueInterface $messageQueue, protected string $source)
    {
    }

    /**
     * Gets the Message Queue for this Consumer
     */
    public function getMessageQueue(): MessageQueueInterface
    {
        return $this->messageQueue;
    }

    /**
     * Sets the Message Queue for this Consumer
     */
    public function setMessageQueue(MessageQueueInterface $messageQueue): static
    {
        $this->messageQueue = $messageQueue;

        return $this;
    }

    /**
     * Gets the source for the messages
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Sets the source for the messages
     */
    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets the message handler
     */
    public function setMessageListener(callable $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Stops the receive process if this has been called
     */
    public function stop(): static
    {
        $this->receiving = false;

        return $this;
    }

    /**
     * Receives messages whilst open
     *
     * TODO: sleep should prevent CPU blocking, test in real world situations if this is helping
     */
    public function receive(int $timeout = 0): void
    {
        $stopAt = time() + $timeout;

        while ($this->receiving && ($timeout === 0 || ($timeout && time() < $stopAt))) {
            if (! $this->receiveNoWait()) {
                sleep(1);
            }
        }

        $this->receiving = true;
    }

    /**
     * Receives the next message if available but does not wait
     */
    public function receiveNoWait(): ?object
    {
        $message = $this->messageQueue->receive($this->source);
        if (! $message || ! $object = @unserialize($message)) {
            return null;
        };

        if ($object instanceof Message) {
            $object = $object->getObject();
        }

        if ($handler = $this->handler) {
            $handler($object);
        }

        return $object;

        return null;
    }
}
