<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\DataMapper\DataSource;

interface DataSourceInterface
{
    public function create(string $table, array $data): bool;
    public function read(string $table, array $query = []): array;
    public function update(string $table, array $data, array $query = []): int;
    public function delete(string $table, array $query = []): int;
    public function count(string $table, array $query = []): int;

    /**
     * Gets the Generated ID by the datasource
     *
     * @return int|string|null
     */
    public function getGeneratedId();
}
