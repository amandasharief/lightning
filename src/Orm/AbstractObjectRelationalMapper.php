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

namespace Lightning\Orm;

use LogicException;
use ReflectionProperty;
use Lightning\Hydrator\Hydrator;
use Lightning\DataMapper\AbstractDataMapper;
use Lightning\EventManager\EventManagerInterface;
use Lightning\DataMapper\DataSource\DataSourceInterface;

/**
 * AbstractORM
 *
 * @internal Joins are not used since all queries should go through hooks and events, using joins would escape these and when going deep you will
 * have to do additional querieis anyway. Also by not using joins then not tying datasource to only relational databases.
 *
 */
abstract class AbstractObjectRelationalMapper extends AbstractDataMapper
{
    /**
      * This also assumes $this->profile is the Profile mapper injected during construction
      *
      * @example
      *  'profile' => [
      *       'className' => Profile::class
      *       'foreignKey' => 'user_id', // in other table
      *       'dependent' => false
      *   ]
      */
    protected array $hasOne = [];

    /**
     * @example
     *   'user' => [
     *       'className' => User::class
     *       'foreignKey' => 'user_id' // in this table
     *   ]
     */

    protected array $belongsTo = [];

    /**
     * @example
     *
     *  'comments' => [
     *      'className' => User::class
     *      'foreignKey' => 'user_id', // in other table
      *     'dependent' => false
     *  ]
     */
    protected array $hasMany = [];

    /**
     * @example
     *
     *  'tags' => [
     *      'className' => User::class
     *      'joinTable' => 'tags_users',
     *      'foreignKey' => 'tag_id',
     *      'otherForeignKey' => 'user_id', // the foreignKey for the associated model
     *      'dependent' => true
     * ]
     */
    protected array $belongsToMany = [];

    protected array $associations = ['belongsTo','hasMany','hasOne','belongsToMany'];

    /**
     * Constructor
     */
    public function __construct(protected DataSourceInterface $dataSource, protected Hydrator $hydrator, protected EventManagerInterface $eventManager, protected DataMapperManager $manager)
    {
        parent::__construct($dataSource, $hydrator, $eventManager); // Best practice call parent construct first

        $this->initializeOrm();
    }

    /**
     * Reads from the DataSource
     */
    protected function doFind(array $query): array
    {
        $resultSet = parent::doFind($query);

        return ! empty($query['with']) && ! empty($resultSet) ? $this->loadRelatedData($resultSet, $query) : $resultSet;
    }

    public function delete(object $entity, array $options = []): bool
    {
        if ($result = parent::delete($entity) && is_string($this->primaryKey)) {
            if ($id = $this->getEntityProperty($entity, $this->primaryKey)) {
                $this->deleteDependent($id);
            }
        }

        return $result;
    }

    /**
     * Check array defintions and add some defaults
     */
    private function initializeOrm(): void
    {
        foreach ($this->associations as $assoc) {
            foreach ($this->$assoc as $property => &$config) {
                $config += [
                    'foreignKey' => null,
                    'className' => null,
                    'dependent' => false,
                    'conditions' => [],
                    'association' => $assoc,
                    'order' => null,
                    'propertyName' => $property
                ];

                if ($assoc === 'belongsTo') {
                    unset($config['dependent']);
                }

                $this->validateAssociationDefinition($assoc, $config);
            }
        }
    }

    /**
     * Validates the defintion array has all the correct keys
     */
    protected function validateAssociationDefinition(string $assoc, array $config): void
    {
        if (empty($config['propertyName'])) {
            throw new LogicException(sprintf('%s is missing propertyName', $assoc));
        }

        if (empty($config['foreignKey'])) {
            throw new LogicException(sprintf('%s `%s` is missing foreignKey', $assoc, $config['propertyName']));
        }

        if (empty($config['className'])) {
            throw new LogicException(sprintf('%s `%s` is missing className', $assoc, $config['propertyName']));
        }

        if ($assoc === 'belongsToMany') {
            if (empty($config['joinTable'])) {
                throw new LogicException(sprintf('belongsToMany `%s` is missing joinTable', $config['propertyName']));
            }
            if (empty($config['otherForeignKey'])) {
                throw new LogicException(sprintf('belongsToMany `%s` is missing otherForeignKey', $config['propertyName']));
            }
        }
    }

    /**
     * Loads the related data
     *
     */
    protected function loadRelatedData(array $resultSet, array $query): array
    {
        // Preload
        $associations = [];
        foreach ($this->associations as $assoc) {
            foreach ($this->$assoc as $config) {
                $property = $config['propertyName'];
                if (in_array($property, $query['with'])) {
                    $associations[$assoc][$property] = $config;
                }
            }
        }

        $primaryKey = $this->getPrimaryKey()[0];

        foreach ($resultSet as &$entity) {
            $row = $this->mapEntityToData($entity);

            foreach ($associations as $type => $association) {
                foreach ($association as $config) {
                    $conditions = $config['conditions'];
                    $options = ['order' => $config['order']];

                    $mapper = $this->manager->get($config['className']);
                    $bindingKey = $mapper->getPrimaryKey()[0];

                    switch ($type) {
                        // TODO: this could be way more effecient, like loading ids then adding later. as right it would depend highly on mysql query cache
                        case 'belongsTo':
                            $conditions[$bindingKey] = $row[$config['foreignKey']];
                            $result = $mapper->findAllBy($conditions, $options);
                            $this->setObjectProperty($entity, $config['propertyName'], $result ? $result[0] : null);

                            break;
                        case 'hasOne':

                            $conditions[$config['foreignKey']] = $row[$primaryKey];
                            $result = $mapper->findAllBy($conditions, $options);
                            $this->setObjectProperty($entity, $config['propertyName'], $result ? $result[0] : null);

                            break;
                        case 'hasMany':
                            $conditions[$config['foreignKey']] = $row[$bindingKey];
                            $this->setObjectProperty($entity, $config['propertyName'], $mapper->findAllBy($conditions, $options));

                            break;
                        case 'belongsToMany':

                            $result = $this->dataSource->read(
                                $config['joinTable'], ['criteria' => [$config['foreignKey'] => $row[$primaryKey]]]
                            );

                            $otherForeignKey = $config['otherForeignKey'];
                            $ids = array_map(function ($record) use ($otherForeignKey) {
                                return $record[$otherForeignKey]; // extract tag_id
                            }, $result);

                            $conditions[$primaryKey] = $ids;
                            $this->setObjectProperty($entity, $config['propertyName'], $mapper->findAllBy($conditions, $options));

                            break;
                    }
                }
            }
        }

        return $resultSet;
    }

    /**
     * Deletes dependent records for the hasOne, hasMany and belongsToMany associations
     *
     * @param string|integer $id
     * @return void
     */
    private function deleteDependent($id): void
    {
        // User has one profile, user_id in other table
        foreach (['hasOne','hasMany'] as $assoc) {
            foreach ($this->$assoc as $config) {
                if (! empty($config['dependent'])) {
                    $mapper = $this->manager->get($config['className']);
                    foreach ($mapper->findAllBy([$config['foreignKey'] => $id]) as $entity) {
                        $mapper->delete($entity);
                    }
                }
            }
        }

        foreach ($this->belongsToMany as $config) {
            if (! empty($config['dependent'])) {
                $this->dataSource->delete($config['joinTable'], ['foreignKey' => $id]);
            }
        }
    }

    private function getEntityProperty(object $entity, string $property): mixed
    {
        $reflectionProperty = new ReflectionProperty($entity, $property);

        return $reflectionProperty->isInitialized($entity) ? $reflectionProperty->getValue($entity) : null;
    }

    /**
     * @internal this is not checking fields, since we are adding RELATED data which is not part of field
     */
    private function setObjectProperty(object $entity, string $property, mixed $value): void
    {
        (new ReflectionProperty($entity, $property))->setValue($entity, $value);
    }
}
