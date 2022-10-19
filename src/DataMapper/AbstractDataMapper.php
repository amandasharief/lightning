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

use App\Command\Person;
use ReflectionProperty;
use BadMethodCallException;
use Lightning\Database\Row;
use InvalidArgumentException;
use Lightning\Utility\Collection;
use Lightning\DataMapper\Exception\EntityNotFoundException;
use Lightning\Entity\PersistableInterface;

abstract class AbstractDataMapper
{
    protected DataSourceInterface $dataSource;

    /**
     * Primary Key
     *
     * @var array<string>|string
     */
    protected $primaryKey = 'id';
    protected string $table = 'none';
    protected string $entityClass;

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
    public function __construct(DataSourceInterface $dataSource)
    {
        $this->dataSource = $dataSource;

        $this->initialize();
    }

    /**
     * A hook that is called when the object is created
     */
    protected function initialize(): void
    {
    }

    /**
     * Checks if the Entity is persisted
     */
    public function isPersisted(object $entity): bool
    {
        return in_array(spl_object_hash($entity), $this->persisted);
    }


    /**
     * Marks an entity as persisted
     */
    public function markPersisted(object $entity, bool $status) : void 
    {
        if($status){
            array_push($this->persisted, spl_object_hash($entity));
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
     * Before create callback
     */
    protected function beforeCreate(object $entity): bool
    {
        return true;
    }

    /**
     * After create callback
     */
    protected function afterCreate(object $entity): void
    {
    }

    /**
     * Before update callback
     */
    protected function beforeUpdate(object $entity): bool
    {
        return true;
    }

    /**
     * after update callback
     */
    protected function afterUpdate(object $entity): void
    {
    }

    /**
     * Before save callback
     */
    protected function beforeSave(object $entity): bool
    {
        return true;
    }

    /**
     * After save callback
     */
    protected function afterSave(object $entity): void
    {
    }

    /**
     * Before delete callback
     */
    protected function beforeDelete(object $entity): bool
    {
        return true;
    }

    /**
     * after delete callback
     */
    protected function afterDelete(object $entity): void
    {
    }

    /**
     * before find callback
     */
    protected function beforeFind(QueryObject $query): bool
    {
        return true;
    }

    /**
     * After find callback
     */
    protected function afterFind(Collection $collection, QueryObject $query): void
    {
    }

    /**
     * Inserts an Entity into the database
     */
    protected function create(object $entity): bool
    {
        if (! $this->beforeCreate($entity)) {
            return false;
        }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));;
        $result = $this->dataSource->create($this->table, $row);

        if ($result) {
            // Add generated ID
            $id = $this->dataSource->getGeneratedId();
            if ($id && is_string($this->primaryKey)) {
                $reflectionProperty = new ReflectionProperty($entity, $this->primaryKey);
                if ($reflectionProperty->isPrivate()) {
                    $reflectionProperty->setAccessible(true); // Only required for PHP 8.0 and lower
                }
                $reflectionProperty->setValue($entity, $id);
            }

            $this->afterCreate($entity);
        }

        return $result;
    }

    /**
     * Saves an Entity
     */
    public function save(object $entity): bool
    {
        if (! $this->beforeSave($entity)) {
            return false;
        }

        $result = $this->isPersisted($entity) ? $this->update($entity) : $this->create($entity);

        if ($result) {
            $this->markPersisted($entity, true);
            $this->afterSave($entity);
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

        return $this->read($query->setOption('limit', 1))->get(0);
    }

    /**
     * Finds multiple Entities
     * @return Collection|object[]
     */
    public function findAll(?QueryObject $query = null): Collection
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

        return $this->beforeFind($query) === false ? 0 : $this->dataSource->count($this->table, $query);
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
    private function convertCollectionToList(Collection $collection, string $keyField, ?string $valueField = null, ?string $groupField = null): array
    {
        $result = [];

        // grouped list
        if ($groupField && $valueField && $keyField) {
            $result = $collection->reduce(function (array $entitites, Row $row) use ($keyField, $valueField, $groupField) {
                $entitites[$row[$groupField] ?? null][$row[$keyField] ?? null] = $row[$valueField] ?? null;

                return $entitites;
            }, []);
        }

        // key value list
        elseif ($valueField && $keyField) {
            $result = $collection->reduce(function (array $entitites, Row $row) use ($keyField, $valueField) {
                $entitites[$row[$keyField] ?? null] = $row[$valueField] ?? null;

                return $entitites;
            }, []);
        }

        // value list
        elseif ($keyField) {
            $result = $collection->reduce(function (array $entitites, Row $row) use ($keyField) {
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
     * @return Collection|object[]
     */
    public function findAllBy(array $criteria, array $options = []): Collection
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
     * Factory method
     */
    public function createCollection(array $items = []): Collection
    {
        return new Collection($items);
    }

    /**
     * Reads from the datasource
     */
    protected function read(QueryObject $query, bool $mapResult = true): Collection
    {
        if (! $this->beforeFind($query)) {
            return $this->createCollection();
        }

        if ($this->fields && ! $query->getOption('fields')) {
            $query->setOption('fields', $this->fields);
        }

        $collection = $this->createCollection($this->dataSource->read($this->table, $query));
        if ($collection->isEmpty()) {
            return $collection;
        }

        $this->afterFind($collection, $query);

        if ($mapResult) {
            foreach ($collection as $index => $row) {
                $collection[$index] = $this->mapDataToEntity($row->toArray());
                $this->markPersisted($collection[$index], true);
            }
        }

        return $collection;
    }

    /**
     * Updates an Entity
     */
    public function update(object $entity): bool
    {
        if (! $this->beforeUpdate($entity)) {
            return false;
        }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));
        $query = $this->createQueryObject($this->getConditionsFromState($row));

        $result = $this->dataSource->update($this->table, $query, $row) === 1;

        if ($result) {
            $this->afterUpdate($entity);
        }

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
        if (! $this->beforeDelete($entity)) {
            return false;
        }

        $row = $this->mapEntityToData($entity);
        $query = $this->createQueryObject($this->getConditionsFromState($row));

        $result = $this->dataSource->delete($this->table, $query) === 1;

        if ($result) {
            $this->markPersisted($entity,false);
            $this->afterDelete($entity);
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
     * Maps state array to entity
     */
    public function mapDataToEntity(array $state): object
    {
        $entity = new $this->entityClass();

        foreach ($state as $key => $value) {
            if (in_array($key, $this->fields)) {
                $reflectionProperty = new ReflectionProperty($entity, $key);
                if ($reflectionProperty->isPrivate()) {
                    $reflectionProperty->setAccessible(true); // Only required for PHP 8.0 and lower
                }
                $reflectionProperty->setValue($entity, $value);
            }
        }

        return $entity;
    }

    /**
     * Converts the entity into a database row
     */
    public function mapEntityToData(object $entity): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $reflectionProperty = new ReflectionProperty($entity, $field);
            
            if ($reflectionProperty->isPrivate()) {
                $reflectionProperty->setAccessible(true); // Only required for PHP 8.0 and lower 
            }

            if( $reflectionProperty->isInitialized($entity)){
                $data[$field] = $reflectionProperty->getValue($entity);
            }
        }

        return $data;
    }

    /**
     * Creates an Entity from an array using mapping.
     */
    public function createEntity(array $data = [], array $options = []): object
    {
        $options += ['fields' => $this->fields,'persisted' => false];
        if ($options['fields']) {
            $data = array_intersect_key($data, array_flip((array) $options['fields']));
        }

        /** @todo think this is redunant now since it is not used internally */
        $entity = $this->mapDataToEntity($data);
        if ($options['persisted']) {
            $this->markPersisted($entity, true);
        }

        return $entity;
    }

    /**
     * Create a collection of Entities
     */
    public function createEntities(array $data, array $options = []): iterable
    {
        return array_map(function ($row) use ($options) {
            return $this->createEntity($row, $options);
        }, $data);
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
