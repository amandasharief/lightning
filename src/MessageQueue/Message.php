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

class Message
{
    private string $id;
    private object $body;
    private int $timestamp;

    /**
     * Constructor
     */
    public function __construct(object $body)
    {
        $this->id = bin2hex(random_bytes(16));
        $this->timestamp = time();
        $this->body = $body;
    }

    /**
     * Gets the message ID
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets the message body
     */
    public function getObject(): object
    {
        return $this->body;
    }

    /**
     * Gets the created timestamp for when this message was created
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
