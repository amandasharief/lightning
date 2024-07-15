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

namespace Lightning\DataMapper\Event;

use Lightning\DataMapper\DataMapperInterface;

/**
 * Before Find Event
 *
 * @internal This event is for modifying the query before it is executed or stopping the query from being executed all together.
 * It is not suppose to bypass the database read or return a different set of results, hence there is no result property.
 */
final class BeforeFind extends AbstractStoppableEvent
{
    public function __construct(private DataMapperInterface $mapper, private array $query)
    {
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->mapper;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }
}
