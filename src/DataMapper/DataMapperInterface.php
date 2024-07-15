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

namespace Lightning\DataMapper;

/**
 * @internal FindList, BatchDelete and BatchUpdate has nothing to do with entities therefore this
 * should not be in the data mapper, these should be custom queries for batch or extract data to create list.
 *
 *
 */
interface DataMapperInterface
{
    public function count(array $options = []): int;
    public function countBy(array $criteria, array $options = []): int;

    public function delete(object $entity, array $options = []): bool;

    public function find(int|string|array $id, array $options = []): ?object;
    public function findAll(array $options = []): iterable;
    public function findAllBy(array $criteria = [], array $options = []): iterable;
    public function findBy(array $criteria = [], array $options = []): ?object;

    /**
     * Gets an Entity by the ID if not throws an exception
     * @throws EntityNotFoundException
     */
    public function get(int|string|array $id, array $options = []): object;

    public function save(object $entity, array $options = []): bool;
}
