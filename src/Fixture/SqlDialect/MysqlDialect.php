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

class MysqlDialect implements SqlDialectInterface
{
    public function disableForeignKeyConstraints(): array
    {
        return [
            'SET FOREIGN_KEY_CHECKS = 0'
        ];
    }

    public function enableForeignKeyConstraints(): array
    {
        return [
            'SET FOREIGN_KEY_CHECKS = 1'
        ];
    }

    public function truncate(string $table): array
    {
        return [
            sprintf('TRUNCATE TABLE %s', $this->quoteIdentifier($table))
        ];
    }

    public function quoteIdentifier(string $identifier): string
    {
        return sprintf('`%s`', $identifier);
    }

    public function resetAutoIncrement(string $table, int $id, string $column = 'id'): array
    {
        return [
            sprintf('ALTER TABLE %s AUTO_INCREMENT = %d', $this->quoteIdentifier($table), $id)
        ];
    }
}
