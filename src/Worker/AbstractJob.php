<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Worker;

use Lightning\Arguments\Arguments;

abstract class AbstractJob implements RunnableInterface, RetryableInterface
{
    private int $attempts = 0;

    protected int $maxRetries = 3;
    protected int $delay = 15;
    private array $args = [];

    /**
     * Hook called when this message is processed
     */
    protected function initialize(): void
    {
    }
    /**
     * Gets the params that will be passed when run
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * Returns a new instance with the arguments set
     */
    public function withArguments(array $args): static
    {
        $service = clone $this;
        $service->args = $args;

        return $service;
    }

    abstract protected function execute(Arguments $args): void;

    /**
     * Runs the JOB
     */
    public function run(): void
    {
        $this->initialize();
        $this->execute(new Arguments($this->args));
    }

    /**
     * Instructs the message object that processing failed
     */
    public function fail(): void
    {
        $this->attempts ++;
    }

    /**
     * Gets the number of attempts
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * Gets the maximum number of times this message processing should be retyied
     */
    public function maxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Seconds to wait before retrying
     */
    public function delay(): int
    {
        return $this->delay;
    }
}
