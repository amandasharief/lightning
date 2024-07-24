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

namespace Lightning\TemplateRenderer\Compiler;

interface TemplateCompilerInterface
{
    /**
     * @param string $path path to template that will be compiled
     * @return string path to compiled template
     */
    public function compile(string $path): string;
}
