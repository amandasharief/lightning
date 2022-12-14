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

namespace Lightning\Worker\Command;

use Lightning\Console\Arguments;
use Lightning\Console\ConsoleIo;
use Lightning\Console\AbstractCommand;
use Lightning\MessageQueue\MessageConsumer;
use Lightning\Console\ConsoleArgumentParser;

/**
 * QueueWorkerCommand
 *
 * @internal This design should NEVER process multiple queues, a worker for each queue. This is basically the MessageConsumer
 */
class QueueWorkerCommand extends AbstractCommand
{
    protected string $name = 'queue:worker';
    protected string $description = 'message queue worker';

    protected $isStopped = false;

    /**
     * Constructor
     */
    public function __construct( ConsoleIo $io, protected MessageConsumer $consumer)
    {
        parent::__construct($io);

        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, [$this, 'stopDaemon']);
            pcntl_signal(SIGINT, [$this, 'stopDaemon']);
        }
    }

    /**
     * Constructor hook
     */
    protected function initialize(): void
    {
        $this->addArgument('queue', [
            'description' => 'The queue where to get the messages from',
            'type' => 'string'
        ]);

        $this->addOption('daemon', [
            'description' => 'Run in daemon mode',
            'type' => 'boolean',
            'short' => 'd',
            'default' => false
        ]);
    }

    /**
     * Command logic is here
     */
    protected function execute(Arguments $args): int
    {
        if ($source = $args->getArgument('queue')) {
            $this->consumer->setSource($source);
        }

        $args->getOption('daemon') ? $this->consumer->receive() : $this->consume();

        return self::SUCCESS;
    }

    private function consume(): void
    {
        if ($this->consumer->receiveNoWait()) {
            $this->consume();
        }
    }

    public function stopDaemon(): void
    {
        $this->out();
        $this->out('<green>> </green><white>Gracefully stopping... (press </white><yellow>Ctrl+C</yellow><white> again to force)</white>');
        $this->consumer->stop();
    }
}
