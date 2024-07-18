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
     * Overwriting the AbstractDataMapper::processRead
     * @todo In future complete method should be here I think.
     */
    protected function processRead(array $query): array
    {
        $result = parent::processRead($query);

        if (! empty($query['with'])) {
            $result = $this->loadRelatedData($result, $query);
        }

        return $result;
    }

    /**
     * Overwriting the AbstractDataMapper::processDelete
     * @todo In future complete method should be here I think.
     */
    protected function processDelete(object $entity, array $options = []): bool
    {
        if ($result = parent::processDelete($entity, $options)) {
            if (is_string(($this->primaryKey))) {
                $reflectionProperty = new ReflectionProperty($entity, $this->primaryKey);
                if ($id = $reflectionProperty->isInitialized($entity) ? $reflectionProperty->getValue($entity) : null) {
                    $this->deleteDependent($id);
                }
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
        // Create associations data from with param
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

        foreach ($resultSet as &$row) {
            foreach ($associations as $type => $association) {
                foreach ($association as $config) {
                    $conditions = $config['conditions'];
                    $options = ['order' => $config['order']];

                    $mapper = $this->manager->get($config['className']);
                    $bindingKey = $mapper->getPrimaryKey()[0];

                    switch ($type) {
                        case 'belongsTo':
                            /**
                             * @todo not efficient. Options: loop through get ids lots of reflection (and even more), single join query like before, caching
                             */
                            $conditions[$bindingKey] = $row[$config['foreignKey']] ?? null;
                            $row[$config['propertyName']] = $mapper->find($conditions, $options);

                            break;
                        case 'hasOne':

                            $conditions[$config['foreignKey']] = $row[$primaryKey] ?? null;
                            $row[$config['propertyName']] = $mapper->find($conditions, $options);

                            break;
                        case 'hasMany':

                            $conditions[$config['foreignKey']] = $row[$bindingKey];
                            $row[$config['propertyName']] = $mapper->findAll($conditions, $options);

                            break;
                        case 'belongsToMany':
                            $result = $this->dataSource->read(
                                $config['joinTable'], ['criteria' => [
                                    $config['foreignKey'] => $row[$primaryKey] ?? null
                                ]]
                            );

                            $otherForeignKey = $config['otherForeignKey'];
                            $ids = array_map(function ($record) use ($otherForeignKey) {
                                return $record[$otherForeignKey]; // extract tag_id
                            }, $result);

                            $conditions[$primaryKey] = $ids;
                            $row[$config['propertyName']] = $mapper->findAll($conditions, $options);

                            break;
                    }
                }
            }
        }
        
        return $resultSet;
    }

    /**
     * Deletes dependent records for the hasOne, hasMany and belongsToMany associations
     */
    private function deleteDependent($id): void
    {
        // User has one profile, user_id in other table
        foreach (['hasOne','hasMany'] as $assoc) {
            foreach ($this->$assoc as $config) {
                if (! empty($config['dependent'])) {
                    $mapper = $this->manager->get($config['className']);
                    foreach ($mapper->findAll([$config['foreignKey'] => $id]) as $entity) {
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
}
