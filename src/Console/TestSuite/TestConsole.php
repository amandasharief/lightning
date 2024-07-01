<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2023 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Console\TestSuite;

use Lightning\Console\Console;

class TestConsole extends Console
{
    public function __construct()
    {
        parent::__construct(
            new OutputStreamStub('php://memory'),
            new OutputStreamStub('php://memory'),
            new InputStreamStub('php://memory')
        );
    }
}
