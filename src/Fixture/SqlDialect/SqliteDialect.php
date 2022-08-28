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

namespace Lightning\Fixture\SqlDialect;

use Lightning\Fixture\SqlDialectInterface;

class SqliteDialect implements SqlDialectInterface
{
    public function disableForeignKeyConstraints(): array
    {
        return [
            'PRAGMA foreign_keys = OFF'
        ];
    }

    public function enableForeignKeyConstraints(): array
    {
        return [
            'PRAGMA foreign_keys = ON'
        ];
    }

    public function truncate(string $table): array
    {
        return [
            sprintf('DELETE FROM %s', $this->quoteIdentifier($table)),
            sprintf('DELETE FROM sqlite_sequence WHERE name = %s', $this->quoteIdentifier($table)),
        ];
    }

    public function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }

    public function resetAutoIncrement(string $table, int $id, string $column = 'id'): array
    {
        return [
            sprintf('SQLITE_SEQUENCE SET SEQ = %d WHERE NAME = %s', $id, $this->quoteIdentifier($table))
        ];
    }
}
