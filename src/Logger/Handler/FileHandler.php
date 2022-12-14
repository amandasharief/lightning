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

namespace Lightning\Logger\Handler;

use Psr\Log\LogLevel;
use DateTimeImmutable;
use Lightning\Logger\LogMessage;
use Lightning\Logger\AbstractHandler;

class FileHandler extends AbstractHandler
{
    /**
     * Constructor
     * @internal Log level should be always last and with a default value
     */
    public function __construct(private string $path, string $level = LogLevel::DEBUG)
    {
        parent::__construct($level);
    }

    /**
     * Handle method
     */
    public function handle(LogMessage $message, string $level, string $channel, DateTimeImmutable $dateTime): bool
    {
        $line = sprintf(
            '[%s] %s %s: %s', $dateTime->format('Y-m-d G:i:s'), $channel, strtoupper($level), $message->toString()
        );

        return (bool) file_put_contents($this->path, $line ."\n", FILE_APPEND);
    }
}
