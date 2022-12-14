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

use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

abstract class AbstractHandler implements HandlerInterface
{
    private int $minLevel = 0;

    private array $logLevels = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY
    ];

    /**
     * Constructor
     */
    public function __construct(string $level)
    {
        $this->setLogLevel($level);
    }

    /**
     * Sets the level of this handler
     */
    public function setLogLevel(string $level): static
    {
        if (! in_array($level, $this->logLevels)) {
            throw new InvalidArgumentException(sprintf('Invalid log level `%s`', $level));
        }
        $this->minLevel = array_search($level, $this->logLevels);

        return $this;
    }

    /**
     * Gets the Level of this handler
     */
    public function getLogLevel(): string
    {
        return $this->logLevels[$this->minLevel];
    }

    /**
     * Gets an instance of this handler with the specificed level
     */
    public function withLogLevel(string $level): static
    {
        return (clone $this)->setLogLevel($level);
    }

    /**
     * Checks if the minimum level is reached
     */
    public function isHandling(string $level): bool
    {
        return array_search($level, $this->logLevels) >= $this->minLevel;
    }
}
