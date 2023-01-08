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
     */
    protected string $name = 'unkown';

    /**
     * Description for this command
     */
    protected string $description = '';

    /**
     * Default error code.
     */
    public const ERROR = 1;

    /**
     * Default success code.
     */
    public const SUCCESS = 0;

    /**
     * List of commands with description
     */
    protected array $commands = [];

    /**
     * @var CommandInterface[]
     */
    protected array $instances = [];

    public function __construct(protected Console $console)
    {
    }

    /**
    * Gets the name of this Command
    */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description for this Command
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Factory method
     */
    private function createHelpFormatter(): ConsoleHelpFormatter
    {
        return new ConsoleHelpFormatter();
    }

    /**
     * Sets the name
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the description
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Adds a command
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
     */
    public function run(array $args): int
    {
        $file = array_shift($args);

        $subCommand = array_shift($args);
        if (! $subCommand || substr($subCommand, 0, 1) === '-') {
            $this->console->out($this->getHelp());

            return self::SUCCESS;
        }

        if (! isset($this->commands[$subCommand])) {
            $this->console->error('`%s` is not a %s command', $subCommand, $this->name);

            return self::ERROR;
        }

        array_unshift($args, $file);

        return $this->instances[$subCommand]->run($args);
    }

    /**
     * Displays the HELP
     */
   public function getHelp(): string
   {
       $help = $this->createHelpFormatter();
       if (! empty($this->description)) {
           $help->setDescription($this->description);
       }

       $help->setUsage([sprintf('%s <command> [options] [arguments]', $this->name)]);
       $help->setCommands($this->commands);

       return $help->generate();
   }
}
