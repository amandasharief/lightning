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

use Lightning\DataMapper\DataSourceInterface;


/**
 * MapperManager
 */
class MapperManager
{
    private DataSourceInterface $dataSource;

    /**
     * @var AbstractObjectRelationalMapper[]
     */
    private array $mappers = [];

    /**
     * @var callable[]
     */
    private array $factoryCallables = [];

    /**
     * Constructor
     */
    public function __construct(DataSourceInterface $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * A factory callable for creating the dataMapper
     *
     * @internal Its not uncommon for to have many data mappers, this method can be used instead of Add so mappers are only created
     * when needed.
     */
    public function configure(string $class, callable $callback): static
    {
        $this->factoryCallables[$class] = $callback;

        return $this;
    }

    /**
     * Adds a Mapper to be managed
     *
     * @internal this is ideal to configure from DI container, problem is its not lazy loaded, so all mappers will be stored even if they
     * are not used.
     */
    public function add(AbstractObjectRelationalMapper $dataMapper): static
    {
        $this->mappers[$dataMapper::class] = $dataMapper;

        return $this;
    }

    /**
     * Gets a DataMapper, creates it if needed
     */
    public function get(string $class): AbstractObjectRelationalMapper
    {
        if (isset($this->mappers[$class])) {
            return $this->mappers[$class];
        }

        return $this->mappers[$class] = $this->createDataMapper($class, $this->dataSource);
    }

    /**
     * Creates the DataMapper object
     */
    protected function createDataMapper(string $class, DataSourceInterface $dataSource): AbstractObjectRelationalMapper
    {
        if (isset($this->factoryCallables[$class])) {
            $callback = $this->factoryCallables[$class];

            return $callback($dataSource, $this);
        }

        return new $class($dataSource, $this);
    }
}
