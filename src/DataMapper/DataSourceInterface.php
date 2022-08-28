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

namespace Lightning\DataMapper;

interface DataSourceInterface
{
    public function create(string $table, array $data): bool;
    public function read(string $table, QueryObject $query): array;
    public function update(string $table, QueryObject $query, array $data): int;
    public function delete(string $table, QueryObject $query): int;
    public function count(string $table, QueryObject $query): int;

    /**
     * Gets the Generated ID by the datasource
     *
     * @return int|string|null
     */
    public function getGeneratedId();
}
