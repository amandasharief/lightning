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

use Lightning\Hydrator\Hydrator;
use Lightning\DataMapper\AbstractDataMapper;
use Lightning\EventManager\EventManagerInterface;
use Lightning\DataMapper\DataSource\DataSourceInterface;

/**
 * DataMapperFactory
 *
 */
class DataMapperFactory implements DataMapperFactoryInterface
{
    public function __construct(protected DataSourceInterface $dataSource, protected Hydrator $hydrator, protected EventManagerInterface $eventManager)
    {
    }

    /**
     * Create the Data Mapper object using the class name
     *
     * @todo Due to the bidirectional nature of the mappers in ORM, i am passing the manager here. Previously
     * creation code was in manager, however it was a more DI container
     */
    public function create(string $dataMapperClass, DataMapperManager $manager): AbstractDataMapper
    {
        switch ($dataMapperClass) {
            // case User::class:
            //     return new $dataMapperClass($this->dataSource, $this->hydrator, $this->eventManager, $manager, $this->someOtherDep);
            //     break;
            default:
                return new $dataMapperClass($this->dataSource, $this->hydrator, $this->eventManager, $manager);
        }
    }
}
