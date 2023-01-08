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

use Lightning\Console\Exception\StopException;

abstract class AbstractCommand implements CommandInterface
{
    protected ConsoleArgumentParser $parser;

    /**
     * Default error code.
     */
    public const ERROR = 1;

    /**
     * Default success code.
     */
    public const SUCCESS = 0;

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
     * Constructor
     */
    public function __construct(protected Console $console)
    {
        $this->parser = $this->createConsoleArgumentParser();
    }

    /**
     * Adds the default options to the argument parser
     */
    private function addDefaultOptions(): void
    {
        $this->addOption('help', [
            'name' => 'help',
            'short' => 'h',
            'description' => 'Displays this help message',
            'type' => 'boolean',
            'required' => false
        ]);
    }

    /**
     * This is a hook that is called when the Command is run
     */
    protected function initialize(): void
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
     * Gets the Console object
     */
    public function getConsole(): Console
    {
        return $this->console;
    }

    /**
     * Factory method
     */
    private function createConsoleArgumentParser(): ConsoleArgumentParser
    {
        return new ConsoleArgumentParser();
    }

    /**
     * Generates the help for this command
     */
    public function getHelp(): string
    {
        $help = new ConsoleHelpFormatter();
        if (! empty($this->description)) {
            $help->setDescription($this->description);
        }

        $help->setUsage([$this->parser->generateUsage($this->name)])
            ->setOptions($this->parser->generateOptions())
            ->setArguments($this->parser->generateArguments());

        return $help->generate();
    }

    /**
     * Adds an option for this command
     */
    public function addOption(string $name, array $options = []): static
    {
        $this->parser->addOption($name, $options);

        return $this;
    }

    /**
     * Adds a argument for this command
     */
    public function addArgument(string $name, array $options = []): static
    {
        $this->parser->addArgument($name, $options);

        return $this;
    }

    /**
     * Place your command logic here
     * @return int|null
     */
    abstract protected function execute(Arguments $args);

    /**
     * Exits the command without an error
     *
     * @throws StopException
     */
    public function exit(): void
    {
        throw new StopException('Command exited', self::SUCCESS);
    }

    /**
     * Aborts this command
     *
     * @throws StopException
     */
    public function abort(int $code = self::ERROR): void
    {
        throw new StopException('Command aborted', $code);
    }

    /**
     * Runs the command
     *
     * Some option names are reserved and if they are enabled by use they will reconfigure the console. e.g. no-input, no-color, quiet
     * @see https://clig.dev
     */
    public function run(array $args): int
    {
        $this->addDefaultOptions();
        $this->initialize();

        array_shift($args);

        // Parse arguments
        $arguments = $this->parser->parse($args);

        if ($arguments->getOption('help') === true) {
            $this->console->out($this->getHelp());

            return self::SUCCESS;
        }

        try {
            return $this->execute($arguments) ?: self::SUCCESS;
        } catch (StopException $exception) {
            return $exception->getCode();
        }
    }
}
