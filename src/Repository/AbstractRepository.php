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

namespace Lightning\Repository;

use Lightning\Utility\Collection;
use Lightning\DataMapper\QueryObject;
use Lightning\DataMapper\AbstractDataMapper;
use Lightning\DataMapper\DataSourceInterface;

/**
 * Repository
 *
 * This is an additional layer on top of the DataMapper
 *
 * @link https://martinfowler.com/eaaCatalog/repository.html
 */
abstract class AbstractRepository
{
    protected AbstractDataMapper $mapper;

    /**
     * Constructor
     *
     * @internal as the mappers are extended to prevent DI problems, these cant be defined as class properties through the constructo.
     * This is no the case with an object that is not extended.
     */
    public function __construct(AbstractDataMapper $mapper)
    {
        $this->mapper = $mapper;

        $this->initialize();
    }

    protected function initialize(): void
    {
    }

    /**
     * Gets an Entity or throws an exception
     *
     * @throws EntityNotFoundException
     */
    public function get(QueryObject $query): object
    {
        return $this->mapper->get($query);
    }

    /**
     * Gets an Entity or throws an exception
     */
    public function getBy(array $criteria = [], array $options = []): object
    {
        return $this->mapper->getBy($criteria, $options);
    }

    /**
     * Finds a single Entity
     */
    public function find(?QueryObject $query = null): ?object
    {
        return $this->mapper->find($query);
    }

    /**
     * Finds multiple Entities
     * @return Collection|object[]
     */
    public function findAll(?QueryObject $query = null): Collection
    {
        return $this->mapper->findAll($query);
    }

    /**
     * Finds the count of Entities that match the query
     */
    public function findCount(?QueryObject $query = null): int
    {
        return $this->mapper->findCount($query);
    }

    /**
     * Finds a list using the query
     *
     * @param array $fields
     *  - keyField: defaults to primary key if it is a string
     *  - valueField: optional
     *  - groupField: optional
     * @return array
     */
    public function findList(?QueryObject $query = null, array $fields = []): array
    {
        return $this->mapper->findList($query, $fields);
    }

    /**
     * Returns a single instance
     */
    public function findBy(array $criteria, array $options = []): ?object
    {
        return $this->mapper->findBy($criteria, $options);
    }

    /**
     * Finds multiple instances
     *
     * @return Collection|object[]
     */
    public function findAllBy(array $criteria, array $options = []): Collection
    {
        return $this->mapper->findAllBy($criteria, $options);
    }

    /**
     * Finds the count of the number of instances
     */
    public function findCountBy(array $criteria, array $options = []): int
    {
        return $this->mapper->findCountBy($criteria, $options);
    }

    /**
     * Finds a list
     *
     * @param array $fields
     *  - keyField: defaults to primary key if it is a string
     *  - valueField: optional
     *  - groupField: optional
     */
    public function findListBy(array $criteria, array $fields = [], array $options = []): array
    {
        return $this->mapper->findListBy($criteria, $fields, $options);
    }

    /**
     * Saves an Entity
     */
    public function save(object $entity): bool
    {
        return $this->mapper->save($entity);
    }

    /**
     * Saves multiple Entities
     */
    public function saveMany(iterable $entities): bool
    {
        return $this->mapper->saveMany($entities);
    }

    /**
     * Deletes an Entity
     */
    public function delete(object $entity): bool
    {
        return $this->mapper->delete($entity);
    }

    /**
     * Deletes multiple entities
     */
    public function deleteMany(iterable $entities): bool
    {
        return $this->mapper->deleteMany($entities);
    }

    /**
     * Deletes all Entities that match the query
     */
    public function deleteAll(QueryObject $query): int
    {
        return $this->mapper->deleteAll($query);
    }

    /**
     * Deletes all Entities that match the criteria
     */
    public function deleteAllBy(array $criteria, array $options = []): int
    {
        return $this->mapper->deleteAllBy($criteria, $options);
    }

    /**
     * Updates all the entities that match the query
     */
    public function updateAll(QueryObject $query, array $data): int
    {
        return $this->mapper->updateAll($query, $data);
    }

    /**
     * Updates all entities that match the criteria
     */
    public function updateAllBy(array $criteria, array $data, array $options = []): int
    {
        return $this->mapper->updateAllBy($criteria, $data, $options);
    }

    /**
     * Creates a new Query object
     */
    public function createQueryObject(array $criteria = [], array $options = []): QueryObject
    {
        return new QueryObject($criteria, $options);
    }

    /**
     * Creates an Entity
     */
    public function createEntity(array $data = [], array $options = []): object
    {
        return $this->mapper->createEntity($data, $options);
    }

    /**
     * Gets the DataSource for this Repository
     */
    public function getDataSource(): DataSourceInterface
    {
        return $this->mapper->getDataSource();
    }

    /**
     * Gets the Data Mapper
     */
    public function getDataMapper(): AbstractDataMapper
    {
        return $this->mapper;
    }
}
