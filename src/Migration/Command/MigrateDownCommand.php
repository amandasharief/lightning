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

use Lightning\Console\Console;
use Lightning\Console\Arguments;
use Lightning\Migration\Migration;
use Lightning\Console\AbstractCommand;

class MigrateDownCommand extends AbstractCommand
{
    protected string $name = 'migrate down';
    protected string $description = 'Migrates the database down.';
    protected int $count = 0;

    /**
     * Constructor
     */
    public function __construct(Console $console, protected Migration $migration)
    {
        parent::__construct($console);
    }

    protected function execute(Arguments $args): int
    {
        $console = $this->getConsole();

        $this->migration->down(function ($migration) use ($console) {
            $console->out("Rolling back migration <info>{$migration['name']}</info>");
            $console->out('');;

            foreach ($migration['statements'] as $sql) {
                $console->out(sprintf('<green> > </green> %s', $sql));
                $console->out('');
            }
            $this->count++;
        });

        $console->out(sprintf('Ran %d migration(s)', $this->count));

        return self::SUCCESS;
    }
}
