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

namespace Lightning\Migration\Command;

use Lightning\Console\Arguments;
use Lightning\Console\ConsoleIo;
use Lightning\Migration\Migration;
use Lightning\Console\AbstractCommand;
use Lightning\Console\ConsoleArgumentParser;

class MigrateUpCommand extends AbstractCommand
{
    protected string $name = 'migrate up';
    protected string $description = 'Migrates the database up.';

    protected int $count = 0;

    protected Migration $migration;

    /**
     * Constructor
     */
    public function __construct( ConsoleIo $io, Migration $migration)
    {
        $this->migration = $migration;
        parent::__construct($io);
    }

    /**
     * Executes the command
     */
    protected function execute(Arguments $args): int
    {
        $this->migration->up(function ($migration) {
            $this->io->out("Running migration <info>{$migration['name']}</info>");

            foreach ($migration['statements'] as $sql) {
                $this->io->out(sprintf('<green> > </green> %s', $sql));
                $this->io->nl();
            }
            $this->count++;
        });

        $this->out(sprintf('Ran %d migration(s)', $this->count));

        return self::SUCCESS;
    }
}
