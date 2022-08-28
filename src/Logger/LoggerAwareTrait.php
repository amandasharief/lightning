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

namespace Lightning\Logger;

use Psr\Log\LoggerInterface;

/**
 * LoggerAwareTrait
 *
 * @internal For Lightning these methods should not be set on any object, they should be added with the trait if desired and user can add to constructor
 * for DI if also required.
 */
trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    /**
     * Get the Logger if available
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }

    /**
     * Set the logger if available
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Logs with an arbitrary level if the Logger is available
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
