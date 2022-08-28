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

class PostgresDialect implements SqlDialectInterface
{
    public function disableForeignKeyConstraints(): array
    {
        return [
            'SET CONSTRAINTS ALL IMMEDIATE'
        ];
    }

    public function enableForeignKeyConstraints(): array
    {
        return [
            'SET CONSTRAINTS ALL DEFERRED'
        ];
    }

    public function truncate(string $table): array
    {
        return [
            sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $this->quoteIdentifier($table))
        ];
    }

    public function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }

    public function resetAutoIncrement(string $table, int $id, string $column = 'id'): array
    {
        return [
            sprintf('ALTER SEQUENCE %s_%s_seq RESTART WITH %d', $table, $column, $id)
        ];
    }
}
