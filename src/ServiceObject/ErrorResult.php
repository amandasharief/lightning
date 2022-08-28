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

namespace Lightning\ServiceObject;

use LogicException;

class ErrorResult extends Result
{
    /**
     * Constructor
     */
    public function __construct(array $data = [])
    {
        parent::__construct(false, $data);
    }

    public function withSuccess(bool $success): static
    {
        throw new LogicException('The success status cannot be changed on ErrorResult');
    }
}
