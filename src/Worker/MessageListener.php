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

use Throwable;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Lightning\MessageQueue\MessageConsumer;
use Lightning\MessageQueue\MessageProducer;

class MessageListener
{
    protected ?LoggerInterface $logger;

    /**
     * Constructor
     */
    public function __construct(protected MessageProducer $producer, protected MessageConsumer $consumer)
    {
    }

    /**
     * Sets the logger for the Worker
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Handles the message
     */
    public function handle(object $message): void
    {
        $this->log(LogLevel::DEBUG, sprintf('%s received', $message::class));

        try {
            $message->run();
            $this->log(LogLevel::INFO, sprintf('%s executed', $message::class));
        } catch (Throwable $exception) {
            $this->log(LogLevel::ERROR, sprintf('%s %s', $message::class, $exception->getMessage()));

            $this->onError($message);
        }
    }

    /**
     * Error handler
     */
    protected function onError(object $message): void
    {
        if ($message instanceof RetryableInterface) {
            $message->fail();

            if ($message->attempts() <= $message->maxRetries()) {
                if (! $this->producer->send($this->consumer->getSource(), $message, $message->delay())) {
                    $this->log(LogLevel::ERROR, sprintf('%s could not be sent to retry', $message::class));
                }
            }
        }
    }

    /**
     * Logs the message if available
     */
    public function log(string $level, string $message): void
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message);
        }
    }

    /**
     * Invokes this object
     */
    public function __invoke(object $message)
    {
        if ($message instanceof RunnableInterface) {
            $this->handle($message);
        } else {
            $this->log(LogLevel::WARNING, sprintf('%s is not runnable', $message::class));
        }
    }
}
