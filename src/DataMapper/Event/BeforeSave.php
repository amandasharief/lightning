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

final class BeforeSave extends AbstractStoppableEvent
{
    public function __construct(private DataMapperInterface $mapper, private object $entity)
    {
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->mapper;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
