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

namespace Lightning\Fixture;

interface SqlDialectInterface
{
    public function disableForeignKeyConstraints(): array;

    public function enableForeignKeyConstraints(): array;

    public function truncate(string $table): array;

    public function quoteIdentifier(string $identifier): string;

    public function resetAutoIncrement(string $table, int $id, string $column = 'id'): array;
}
