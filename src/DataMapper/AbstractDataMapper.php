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

use ReflectionProperty;
use BadMethodCallException;
use Lightning\Database\Row;
use InvalidArgumentException;
use Lightning\Hydrator\Hydrator;
use Lightning\DataMapper\Exception\EntityNotFoundException;

abstract class AbstractDataMapper
{
    /**
     * Primary Key
     *
     * @var array<string>|string
     */
    protected $primaryKey = 'id';
    protected string $table = 'none';

    /**
     * These are the fields that DataMapper works with
     */
    protected array $fields = [];

    /**
     * hashes of entities persisted
     */
    private array $persisted = [];

    /**
     * Constructor
     */
    public function __construct(protected DataSourceInterface $dataSource, protected Hydrator $hydrator)
    {
        $this->initialize();
    }

    /**
     * A hook that is called when the object is created
     */
    protected function initialize(): void
    {
    }

    /**
     * Factory method for creating an Entity associated with this Data Mapper
     */
    abstract public function createEntity(): object;

    /**
     * Checks if the Entity is persisted
     */
    public function isPersisted(object $entity): bool
    {
        return in_array(spl_object_id($entity), $this->persisted);
    }

    /**
     * Marks an entity as persisted
     */
    public function markPersisted(object $entity, bool $status): void
    {
        if ($status) {
            array_push($this->persisted, spl_object_id($entity));

            return;
        }

        $key = array_search(spl_object_hash($entity), $this->persisted);
        if ($key !== false) {
            unset($this->persisted[$key]);
        }
    }

    /**
     * Gets primary key used by this Mapper
     */
    public function getPrimaryKey(): array
    {
        return (array) $this->primaryKey;
    }

    /**
     * Gets the DataSource for this Mapper
     */
    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }

    /**
     * Inserts an Entity into the database
     */
    protected function create(object $entity): bool
    {
        // if (! $this->beforeCreate($entity)) {
        //     return false;
        // }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));
        ;
        $result = $this->dataSource->create($this->table, $row);

        if ($result) {
            // Add generated ID
            $id = $this->dataSource->getGeneratedId();
            if ($id && is_string($this->primaryKey)) {
                $reflectionProperty = new ReflectionProperty($entity, $this->primaryKey);
                $reflectionProperty->setValue($entity, $id);
            }

            // $this->afterCreate($entity);
        }

        return $result;
    }

    /**
     * Saves an Entity
     */
    public function save(object $entity): bool
    {
        // if (! $this->beforeSave($entity)) {
        //     return false;
        // }

        $result = $this->isPersisted($entity) ? $this->update($entity) : $this->create($entity);

        if ($result) {
            $this->markPersisted($entity, true);
            // $this->afterSave($entity);
        }

        return $result;
    }

    /**
     * Gets an Entity or throws an exception
     * @throws EntityNotFoundException
     */
    public function get(QueryObject $query): object
    {
        $result = $this->find($query);

        if (! $result) {
            throw new EntityNotFoundException('Entity Not Found');
        }

        return $result;
    }

    /**
     * Finds a single Entity
     */
    public function find(?QueryObject $query = null): ?object
    {
        $query = $query ?? $this->createQueryObject();

        return $this->read($query->setOption('limit', 1))[0] ?? null;
    }

    /**
     * Finds multiple Entities
     * @return object[]
     */
    public function findAll(?QueryObject $query = null): array
    {
        $query = $query ?? $this->createQueryObject();

        return $this->read($query);
    }

    /**
     * Finds the count of Entities that match the query
     */
    public function findCount(?QueryObject $query = null): int
    {
        $query = $query ?? $this->createQueryObject();

        return $this->dataSource->count($this->table, $query);
        // return $this->beforeFind($query) === false ? 0 : $this->dataSource->count($this->table, $query);
    }

    /**
     * Finds a list using the query
     *
     * @param QueryObject|null $query
     * @param array $fields
     *  - keyField: defaults to primary key if it is a string
     *  - valueField: optional
     *  - groupField: optional
     * @return array
     */
    public function findList(?QueryObject $query = null, array $fields = []): array
    {
        $query = $query ?? $this->createQueryObject();

        $keyField = $fields['keyField'] ?? (is_string($this->primaryKey) ? $this->primaryKey : null);
        if (! $keyField) {
            throw new InvalidArgumentException('Cannot determine primary key');
        }

        return $this->convertCollectionToList(
            $this->read($query, false),
            $keyField, $fields['valueField'] ?? null, $fields['groupField'] ?? null
        );
    }

    /**
     * Converts the Collection to a list
     */
    private function convertCollectionToList(array $collection, string $keyField, ?string $valueField = null, ?string $groupField = null): array
    {
        $result = [];

        // grouped list
        if ($groupField && $valueField && $keyField) {
            $result = array_reduce($collection, function (array $entitites, Row $row) use ($keyField, $valueField, $groupField) {
                $entitites[$row[$groupField] ?? null][$row[$keyField] ?? null] = $row[$valueField] ?? null;

                return $entitites;
            }, []);
        }

        // key value list
        elseif ($valueField && $keyField) {
            $result = array_reduce($collection, function (array $entitites, Row $row) use ($keyField, $valueField) {
                $entitites[$row[$keyField] ?? null] = $row[$valueField] ?? null;

                return $entitites;
            }, []);
        }

        // value list
        elseif ($keyField) {
            $result = array_reduce($collection, function (array $entitites, Row $row) use ($keyField) {
                $entitites[] = $row[$keyField] ?? null;

                return $entitites;
            }, []);
        }

        return $result;
    }

    /**
     * Gets an Entity or throws an exception
     */
    public function getBy(array $criteria = [], array $options = []): object
    {
        return $this->get($this->createQueryObject($criteria, $options));
    }

    /**
     * Returns a single instance
     *
     * @param array $options Options vary between datasources, but the following should be supported
     *  - limit
     *  - offset
     *  - sort
     * @return object|null
     */
    public function findBy(array $criteria = [], array $options = []): ?object
    {
        return $this->find($this->createQueryObject($criteria, $options));
    }

    /**
     * Finds multiple instances
     * @return object[]
     */
    public function findAllBy(array $criteria, array $options = []): array
    {
        return $this->findAll($this->createQueryObject($criteria, $options));
    }

    /**
     * Finds the count of the number of instances
     */
    public function findCountBy(array $criteria, array $options = []): int
    {
        return $this->findCount($this->createQueryObject($criteria, $options));
    }

    /**
     * Finds a list
     * @param array $fields
     *  - keyField: defaults to primary key if it is a string
     *  - valueField: optional
     *  - groupField: optional
     */
    public function findListBy(array $criteria, array $fields = [], array $options = []): array
    {
        return $this->findList($this->createQueryObject($criteria, $options), $fields);
    }

    /**
     * Reads from the datasource
     */
    protected function read(QueryObject $query, bool $mapResult = true): array
    {
        // if (! $this->beforeFind($query)) {
        //     return [];
        // }

        if ($this->fields && ! $query->getOption('fields')) {
            $query->setOption('fields', $this->fields);
        }

        if (! $result = $this->dataSource->read($this->table, $query)) {
            return [];
        }

        // $this->afterFind($result, $query);

        if ($mapResult) {
            foreach ($result as $index => $row) {
                $result[$index] = $this->mapDataToEntity($row->toArray());
                $this->markPersisted($result[$index], true);
            }
        }

        return $result;
    }

    /**
     * Updates an Entity
     */
    public function update(object $entity): bool
    {
        // if (! $this->beforeUpdate($entity)) {
        //     return false;
        // }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));
        $query = $this->createQueryObject($this->getConditionsFromState($row));

        $result = $this->dataSource->update($this->table, $query, $row) === 1;

        // if ($result) {
        //     $this->afterUpdate($entity);
        // }

        return $result;
    }

    /**
     * Updates records that match query with the data provided but no events or hooks will be triggered
     */
    public function updateAll(QueryObject $query, array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }

        return $this->dataSource->update($this->table, $query, $data);
    }

    /**
     * Deletes records that match the query but no events or hooks will be triggered
     */
    public function deleteAll(QueryObject $query): int
    {
        return $this->dataSource->delete($this->table, $query);
    }

    /**
     * Saves a collection of entities
     */
    public function saveMany(iterable $entities): bool
    {
        foreach ($entities as $entity) {
            if (! $this->save($entity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a collection of entities
     */
    public function deleteMany(iterable $entities): bool
    {
        foreach ($entities as $entity) {
            if (! $this->delete($entity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates a new Query object
     */
    public function createQueryObject(array $criteria = [], array $options = []): QueryObject
    {
        return new QueryObject($criteria, $options);
    }

    /**
     * Deletes an entity
     */
    public function delete(object $entity): bool
    {
        // if (! $this->beforeDelete($entity)) {
        //     return false;
        // }

        $row = $this->mapEntityToData($entity);
        $query = $this->createQueryObject($this->getConditionsFromState($row));

        $result = $this->dataSource->delete($this->table, $query) === 1;

        if ($result) {
            $this->markPersisted($entity, false);
            // $this->afterDelete($entity);
        }

        return $result;
    }

    /**
     * Deletes records that match the criteria but no events or hooks will be triggered
     */
    public function deleteAllBy(array $criteria, array $options = []): int
    {
        return $this->deleteAll($this->createQueryObject($criteria, $options));
    }

    /**
     * Updates records that match criteria with the data provided but no events or hooks will be triggered
     */
    public function updateAllBy(array $criteria, array $data, array $options = []): int
    {
        return $this->updateAll($this->createQueryObject($criteria, $options), $data);
    }

    /**
     * Converts a row from the storage into an Entity object
     */
    public function mapDataToEntity(array $state): object
    {
        $entity = $this->createEntity();

        $this->hydrator->hydrate(
            $entity, array_intersect_key($state, array_flip((array) $this->fields))
        );

        return $entity;
    }

    /**
     * Converts Entity object into an array ready to be persisted to storage
     */
    public function mapEntityToData(object $entity): array
    {
        $extracted = $this->hydrator->extract($entity);

        return  array_intersect_key($extracted, array_flip((array) $this->fields));
    }

    /**
     * Creates the conditions array from a particular entity
     */
    protected function getConditionsFromState(array $state): array
    {
        $conditions = [];

        foreach ((array) $this->primaryKey as $key) {
            if (! isset($state[$key])) {
                throw new BadMethodCallException(sprintf('Primary key `%s` has no value', $key));
            }
            $conditions[$key] = $state[$key];
        }

        return $conditions;
    }
}
