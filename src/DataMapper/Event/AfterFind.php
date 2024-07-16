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
 * After Find Event
 */
final class AfterFind
{
    public function __construct(private DataMapperInterface $mapper, private iterable $result)
    {
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->mapper;
    }

    public function getResult(): iterable
    {
        return $this->result;
    }

    public function setResult(iterable $result): self
    {
        $this->result = $result;

        return $this;
    }
}
