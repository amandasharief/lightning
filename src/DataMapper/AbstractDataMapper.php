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
use Lightning\Hydrator\Hydrator;
use Lightning\DataMapper\Event\AfterFind;
use Lightning\DataMapper\Event\AfterSave;
use Lightning\Hydrator\HydratorInterface;
use Lightning\DataMapper\Event\BeforeFind;
use Lightning\DataMapper\Event\BeforeSave;
use Lightning\DataMapper\Event\AfterCreate;
use Lightning\DataMapper\Event\AfterDelete;
use Lightning\DataMapper\Event\AfterUpdate;
use Lightning\DataMapper\Event\BeforeCreate;
use Lightning\DataMapper\Event\BeforeDelete;
use Lightning\DataMapper\Event\BeforeUpdate;
use Lightning\EventManager\EventManagerInterface;
use Lightning\DataMapper\DataSource\DataSourceInterface;
use Lightning\DataMapper\Exception\EntityNotFoundException;

/**
 * Data Mapper
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    /**
     * @var string|array<string>
     */
    protected string|array $primaryKey = 'id';
    protected string $table = 'none';

    /**
     * These are the fields that DataMapper works with
     */
    protected array $fields = [];

    private array $persisted = [];  //hashes of entities persisted

    public function __construct(protected DataSourceInterface $dataSource, protected Hydrator $hydrator, protected EventManagerInterface $eventManager)
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

    # # # # START INTERFACE FUNCTIONS # # # #

    /**
     * Finds the count of Entities that match the query
     */
    public function count(array $options = []): int
    {
        return $this->doCount($options + ['criteria' => []]);
    }

    /**
     * Finds the count of Entities that match the query
     */
    public function countBy(array $criteria, array $options = []): int
    {
        return $this->doCount($options + ['criteria' => $criteria]);
    }

    /**
     * Deletes an entity
     */
    public function delete(object $entity, array $options = []): bool
    {
        return $this->doDelete($entity, $options);
    }

    /**
     * Finds a single Entity
     */
    public function find(int|string|array $id, array $options = []): ?object
    {
        return $this->doFind(array_merge($options, ['criteria' => $this->createCriteriaFromId($id), 'limit' => 1]))[0] ?? null;
    }

    /**
     * Finds multiple Entities
     * @param array $options The following options are supported
     * - order: e.g id DESC
     * - limit: e.g 5
     */
    public function findAll(array $options = []): array
    {
        return $this->doFind(array_merge($options, ['criteria' => []]));
    }

    /**
     * Finds multiple Entities
     * @param array $options The following options are supported
     * - order: e.g id DESC
     * - limit: e.g 5
     */
    public function findAllBy(array $criteria = [], array $options = []): array
    {
        return $this->doFind(array_merge($options, ['criteria' => $criteria]));
    }

    /**
     * @param array $options The following options are supported
     * - order: e.g id DESC
     */
    public function findBy(array $criteria = [], array $options = []): ?object
    {
        return $this->doFind(array_merge($options, ['criteria' => $criteria, 'limit' => 1]))[0] ?? null;
    }

    /**
     * Gets an Entity by the ID if not throws an exception
     *
     * @throws EntityNotFoundException
     */
    public function get(int|string|array $id, array $options = []): object
    {
        $result = $this->doFind(array_merge($options, ['criteria' => $this->createCriteriaFromId($id), 'limit' => 1]))[0] ?? null;
        if (! $result) {
            throw new EntityNotFoundException('Entity Not Found');
        }

        return $result;
    }

    /**
     * Save the Entity
     */
    public function save(object $entity, array $options = []): bool
    {
        if ($this->eventManager->hasListeners(BeforeSave::class)) {
            if ($this->eventManager->dispatch(new BeforeSave($this, $entity))->isPropagationStopped()) {
                return false;
            }
        }

        $result = $this->isPersisted($entity) ? $this->doUpdate($entity) : $this->doCreate($entity);

        if ($result) {
            $this->markPersisted($entity, true);
            if ($this->eventManager->hasListeners(AfterSave::class)) {
                $this->eventManager->dispatch(new AfterSave($this, $entity));
            }
        }

        return $result;
    }

    # # # # END INTERFACE FUNCTIONS # # # #

    /**
     * Converts a row from the storage into an Entity object
     */
    public function mapDataToEntity(array $data): object
    {
        $entity = $this->createEntity();

        $this->hydrator->hydrate($entity, array_intersect_key($data, array_flip($this->fields)));

        return $entity;
    }

    /**
     * Converts Entity object into an array ready to be persisted to storage
     */
    public function mapEntityToData(object $entity): array
    {
        $extracted = $this->hydrator->extract($entity);

        return array_intersect_key($extracted, array_flip($this->fields));
    }

    /**
     * Gets primary key used by this Mapper
     */
    public function getPrimaryKey(): array
    {
        return (array) $this->primaryKey;
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }

    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
    }

    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    protected function doCount(array $query): int
    {
        if ($this->eventManager->hasListeners(BeforeFind::class)) {
            $event = $this->eventManager->dispatch(new BeforeFind($this, $query));
            if ($event->isPropagationStopped()) {
                return 0;
            }
            $query = $event->getQuery();
        }

        return $this->dataSource->count($this->table, $query);
    }

    protected function doCreate(object $entity): bool
    {
        if ($this->eventManager->hasListeners(BeforeCreate::class)) {
            if ($this->eventManager->dispatch(new BeforeCreate($this, $entity))->isPropagationStopped()) {
                return false;
            }
        }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));

        $result = $this->dataSource->create($this->table, $row);
        if ($result) {
            // Add generated ID
            $id = $this->dataSource->getGeneratedId();
            if ($id && is_string($this->primaryKey)) {
                $reflectionProperty = new ReflectionProperty($entity, $this->primaryKey);
                $reflectionProperty->setValue($entity, $id);
            }

            if ($this->eventManager->hasListeners(AfterCreate::class)) {
                $this->eventManager->dispatch(new AfterCreate($this, $entity));
            }
        }

        return $result;
    }

    /**
     * Updates an Entity
     */
    protected function doUpdate(object $entity): bool
    {
        if ($this->eventManager->hasListeners(BeforeUpdate::class)) {
            if ($this->eventManager->dispatch(new BeforeUpdate($this, $entity))->isPropagationStopped()) {
                return false;
            }
        }

        $row = array_intersect_key($this->mapEntityToData($entity), array_flip($this->fields));

        $result = $this->dataSource->update(
            $this->table, $row, ['criteria' => $this->createCriteriaFromState($row)]
        ) === 1;

        if ($this->eventManager->hasListeners(AfterUpdate::class)) {
            $this->eventManager->dispatch(new AfterUpdate($this, $entity));
        }

        return $result;
    }

    /**
     * Reads from the datasource
     */
    protected function doFind(array $query): array
    {
        if ($this->eventManager->hasListeners(BeforeFind::class)) {
            $event = $this->eventManager->dispatch(new BeforeFind($this, $query));
            if ($event->isPropagationStopped()) {
                return [];
            }
            $query = $event->getQuery();
        }

        if (! $result = $this->dataSource->read($this->table, ['fields' => $this->fields] + $query)) {
            return [];
        }

        foreach ($result as $index => $row) {
            $result[$index] = $this->mapDataToEntity($row->toArray());
            $this->markPersisted($result[$index], true);
        }

        if ($this->eventManager->hasListeners(AfterFind::class)) {
            $result = $this->eventManager->dispatch(new AfterFind($this, $result))->getResult();
        }

        return $result;
    }

    /**
     * Do delete
     */
    protected function doDelete(object $entity, array $options = []): bool
    {
        if ($this->eventManager->hasListeners(BeforeDelete::class)) {
            if ($this->eventManager->dispatch(new BeforeDelete($this, $entity))->isPropagationStopped()) {
                return false;
            }
        }

        $criteria = $this->createCriteriaFromState($this->mapEntityToData($entity));
        $result = $this->dataSource->delete($this->table, array_merge($options, ['criteria' => $criteria])) === 1;

        if ($result) {
            $this->markPersisted($entity, false);
            if ($this->eventManager->hasListeners(AfterDelete::class)) {
                $this->eventManager->dispatch(new AfterDelete($this, $entity));
            }
        }

        return $result;
    }

    /**
     * Checks if the Entity is persisted
     */
    protected function isPersisted(object $entity): bool
    {
        return isset($this->persisted[spl_object_id($entity)]);
    }

    /**
     * Marks an entity as persisted
     */
    protected function markPersisted(object $entity, bool $status): void
    {
        if ($status) {
            $this->persisted[spl_object_id($entity)] = $status;

            return;
        }

        unset($this->persisted[spl_object_id($entity)]);
    }

    /**
     * Creates the conditions array from a particular entity
     */
    private function createCriteriaFromState(array $state): array
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

    /**
     * If the primary key is ['post_id','tag_id'] the ID should be something like [1000,1001]
     */
    private function createCriteriaFromId(int|string|array $id): array
    {
        $id = (array) $id;
        $primaryKey = (array) $this->primaryKey;

        if (count($id) !== count($primaryKey)) {
            throw new BadMethodCallException('Invalid Primary Key / ID');
        }

        $criteria = [];
        foreach ($primaryKey as $index => $field) {
            $criteria[$field] = $id[$index];
        }

        return $criteria;
    }
}
