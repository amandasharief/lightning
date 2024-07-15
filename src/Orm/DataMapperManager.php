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

/**
 * DataMapperManager
 */
final class DataMapperManager
{
    private array $dataMappers = [];

    public function __construct(private DataMapperFactoryInterface $factory)
    {
    }

    /**
       * Adds a already constructed Data Mapper to be managed
       *
       * @internal this is ideal to configure from DI container, problem is its not lazy loaded, so all mappers will be stored even if they
       * are not used.
       */
    public function add(AbstractObjectRelationalMapper $dataMapper): static
    {
        $this->dataMappers[$dataMapper::class] = $dataMapper;

        return $this;
    }

    /**
     * Gets the Data Mapper if its available, if not it will create and then return
     */
    public function get(string $class): AbstractObjectRelationalMapper
    {
        if (isset($this->dataMappers[$class])) {
            return $this->dataMappers[$class];
        }

        return $this->dataMappers[$class] = $this->factory->create($class, $this);
    }
}
