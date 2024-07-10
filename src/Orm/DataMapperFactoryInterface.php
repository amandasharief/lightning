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

use Lightning\DataMapper\AbstractDataMapper;

interface DataMapperFactoryInterface
{
    /**
     * Create a Data Mapper or throw an exception
     */
    public function create(string $dataMapperClass, DataMapperManager $manager): AbstractDataMapper;
}
