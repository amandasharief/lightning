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

namespace Lightning\Orm;

use LogicException;
use ReflectionProperty;
use Lightning\Utility\Collection;
use Lightning\DataMapper\QueryObject;
use Lightning\DataMapper\AbstractDataMapper;
use Lightning\DataMapper\DataSourceInterface;

/**
 * AbstractORM
 *
 * @internal Joins are not used since all queries should go through hooks and events, using joins would escape these and when going deep you will
 * have to do additional querieis anyway. Also by not using joins then not tying datasource to only relational databases.
 *
 */
abstract class AbstractObjectRelationalMapper extends AbstractDataMapper
{
    protected MapperManager $mapperManager;

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
    public function __construct(DataSourceInterface $dataSource, MapperManager $mapperManager)
    {
        $this->mapperManager = $mapperManager;
        parent::__construct($dataSource);

        $this->initializeOrm();
    }

    /**
     * Reads from the DataSource
     */
    protected function read(QueryObject $query, bool $mapResult = true): Collection
    {
        $resultSet = parent::read($query, $mapResult);

        return $query->getOption('with') && $resultSet->isEmpty() === false ? $this->loadRelatedData($resultSet, $query) : $resultSet;
    }

    public function delete(object $entity): bool
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
                    'fields' => [],
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
     *
     * @param string $assoc
     * @param array $config
     * @return void
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
    protected function loadRelatedData(Collection $resultSet, QueryObject $query): Collection
    {
        $options = $query->getOptions();

        // Preload
        $associations = [];
        foreach ($this->associations as $assoc) {
            foreach ($this->$assoc as $config) {
                $property = $config['propertyName'];
                if (in_array($property, $options['with'])) {
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
                    $options = ['fields' => $config['fields'], 'order' => $config['order']];

                    $mapper = $this->mapperManager->get($config['className']);
                    $bindingKey = $mapper->getPrimaryKey()[0];

                    switch ($type) {
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
                                    $config['joinTable'], new QueryObject([$config['foreignKey'] => $row[$primaryKey]])
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
                    $mapper = $this->mapperManager->get($config['className']);
                    foreach ($mapper->findAllBy([$config['foreignKey'] => $id]) as $entity) {
                        $mapper->delete($entity);
                    }
                }
            }
        }

        foreach ($this->belongsToMany as $config) {
            if (! empty($config['dependent'])) {
                $this->dataSource->delete($config['joinTable'], new QueryObject([$config['foreignKey'] => $id]));
            }
        }
    }

    private function getEntityProperty(object $entity, string $property): mixed
    {
        $result = null;
        $reflectionProperty = new ReflectionProperty($entity, $property);
        if ($reflectionProperty->isPrivate()) {
            $reflectionProperty->setAccessible(true); // Only required for PHP 8.0 and lower
        }

        if ($reflectionProperty->isInitialized($entity)) {
            $result = $reflectionProperty->getValue($entity);
        }

        return  $result ;
    }

    /**
     * @internal this is not checking fields, since we are adding RElATED data which is not part of field
     */
    private function setObjectProperty(object $entity, string $property, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($entity, $property);

        if ($reflectionProperty->isPrivate()) {
            $reflectionProperty->setAccessible(true); // Only required for PHP 8.0 and lower
        }

        $reflectionProperty->setValue($entity, $value);
    }
}
