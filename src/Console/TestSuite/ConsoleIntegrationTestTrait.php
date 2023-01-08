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

namespace Lightning\Console\TestSuite;

use RuntimeException;
use Lightning\Console\Console;
use Lightning\Console\AbstractCommand;

trait ConsoleIntegrationTestTrait
{
    /**
     * setupIntegrationTesting creates
     */
    private ?OutputStreamStub $stdout = null;

    /**
     * Created by setupIntegrationTesting
     */
    private ?OutputStreamStub  $stderr = null;

    /**
     * Created by setupIntegrationTesting
     */
    private ?InputStreamStub $stdin = null;

    private ?TestConsole $console = null;

    private ?AbstractCommand $command = null;
    private ?int $commandExitCode = null;

    /**
     * Sets up the integration testing by setting the command extracting streams
     */
    public function setupIntegrationTesting(AbstractCommand $command): void
    {
        $this->command = $command;
        $this->console = $command->getConsole(); // Command should be setup with test console
        $this->stdout = $this->console->stdout;
        $this->stderr = $this->console->stderr;
        $this->stdin = $this->console->stdin;
    }

    /**
     * Creates the TestConsole object
     */
    public function createTestConsole(): TestConsole
    {
        return new TestConsole();
    }

    /**
     * Gets the console
     */
    public function getTestConsole(): TestConsole
    {
        if (! isset($this->console)) {
            throw new RuntimeException('Integration testing not Setup');
        }

        return $this->console;
    }

    /**
    * Gets the Console IO Stub object
    */
    protected function getCommand(): AbstractCommand
    {
        if (! isset($this->command)) {
            throw new RuntimeException('Command not set');
        }

        return $this->command;
    }

    /**
     * Executes a command
     */
    public function execute(array $args = [], array $input = []): bool
    {
        array_unshift($args, 'bin/run');

        $this->stdin->setInput($input);

        $this->commandExitCode = $this->getCommand()->run($args);

        return $this->commandExitCode === AbstractCommand::SUCCESS;
    }

    /**
     * Asserts the exit code was a success
     */
    public function assertExitSuccess(): void
    {
        $this->assertEquals($this->commandExitCode, AbstractCommand::SUCCESS);
    }

    /**
     * Asserts the exit code was an error
     */
    public function assertExitError(): void
    {
        $this->assertNotEquals($this->commandExitCode, AbstractCommand::SUCCESS);
    }

    /**
     * Asserts an exit code
     */
    public function assertExitCode(int $code): void
    {
        $this->assertEquals($this->commandExitCode, $code);
    }

    /**
     * Assert Output contains
     */
    public function assertOutputContains(string $message): void
    {
        $this->assertStringContainsString($message, $this->stdout->getContents());
    }

    /**
    * Assert Output does not contains
    */
    public function assertOutputNotContains(string $message): void
    {
        $this->assertStringNotContainsString($message, $this->stdout->getContents());
    }

    /**
     * Asserts that the output was empty
     */
    public function assertOutputEmpty(): void
    {
        $this->assertEmpty($this->stdout->getContents());
    }

    /**
     * Asserts that the output was empty
     */
    public function assertOutputNotEmpty(): void
    {
        $this->assertNotEmpty($this->stdout->getContents());
    }

    /**
     * Asserts that output matches a regualar expression
     */
    public function assertOutputMatchesRegularExpression(string $pattern): void
    {
        $this->assertMatchesRegularExpression($pattern, $this->stdout->getContents());
    }

    /**
     * Asserts that the output does not match a regular expression
     */
    public function assertOutputDoesNotMatchRegularExpression(string $pattern): void
    {
        $this->assertDoesNotMatchRegularExpression($pattern, $this->stdout->getContents());
    }

    /**
     * Assert Error contains
     */
    public function assertErrorContains(string $message): void
    {
        $this->assertStringContainsString($message, $this->stderr->getContents());
    }

    /**
    * Assert error output does not contains
    */
    public function assertErrorNotContains(string $message): void
    {
        $this->assertStringNotContainsString($message, $this->stderr->getContents());
    }

    /**
     * Asserts that the error output was empty
     */
    public function assertErrorEmpty(): void
    {
        $this->assertEmpty($this->stderr->getContents());
    }

    /**
     * Asserts that the error output was empty
    */
    public function assertErrorNotEmpty(): void
    {
        $this->assertNotEmpty($this->stderr->getContents());
    }

    /**
     * Asserts that error output matches a regualar expression
     */
    public function assertErrorMatchesRegularExpression(string $pattern): void
    {
        $this->assertMatchesRegularExpression($pattern, $this->stderr->getContents());
    }

    /**
     * Asserts that the error output does not match a regular expressionattern
     */
    public function assertErrorDoesNotMatchRegularExpression(string $pattern): void
    {
        $this->assertDoesNotMatchRegularExpression($pattern, $this->stderr->getContents());
    }
}
