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

namespace Lightning\Console;

class ConsoleApplication implements CommandInterface
{
    /**
     * Name of this command, when working with sub commands you can use spaces for example
     * `migrate up` this will then show up in the help and allow you to use this properly
     *
     * @var string
     */
    protected string $name = 'unkown';

    /**
     * Description for this command
     *
     * @var string
     */
    protected string $description = '';

    /**
     * Default error code.
     *
     * @var int
     */
    public const ERROR = 1;

    /**
     * Default success code.
     *
     * @var int
     */
    public const SUCCESS = 0;

    /**
     * List of commands with description
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * @var CommandInterface[]
     */
    protected array $instances = [];

    protected ConsoleIo $io;

    /**
     * Console IO
     *
     * @param ConsoleIo $io
     */
    public function __construct(ConsoleIo $io)
    {
        $this->io = $io;
    }

    /**
    * Gets the name of this Command
    *
    * @return string
    */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description for this Command
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Factory method
     *
     * @return ConsoleHelpFormatter
     */
    private function createHelpFormatter(): ConsoleHelpFormatter
    {
        return new ConsoleHelpFormatter();
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return static
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Adds a command
     *
     * @param CommandInterface $command
     * @return static
     */
    public function add(CommandInterface $command): static
    {
        $names = explode(' ', $command->getName());
        $name = end($names);

        $this->commands[$name] = $command->getDescription();
        $this->instances[$name] = $command;

        return $this;
    }

    /**
     * Runs the Console Application with the arguments
     *
     * @param array $args
     * @return integer
     */
    public function run(array $args): int
    {
        $file = array_shift($args);

        $subCommand = array_shift($args);
        if (! $subCommand || substr($subCommand, 0, 1) === '-') {
            $this->displayHelp();

            return self::SUCCESS;
        }

        if (! isset($this->commands[$subCommand])) {
            $this->io->err(sprintf('`%s` is not a %s command', $subCommand, $this->name));

            return self::ERROR;
        }

        array_unshift($args, $file);

        return $this->instances[$subCommand]->run($args);
    }

    /**
     * Displays the HELP
     *
     * @return void
     */
    private function displayHelp(): void
    {
        $help = $this->createHelpFormatter();
        if (! empty($this->description)) {
            $help->setDescription($this->description);
        }

        $help->setUsage([sprintf('%s <command> [options] [arguments]', $this->name)]);
        $help->setCommands($this->commands);

        $this->io->out($help->generate());
    }
}
