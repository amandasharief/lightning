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

namespace Lightning\TemplateRenderer;

interface TemplateRendererInterface
{
    /**
     * Renders a template
     *
     * @param string $template the template to render
     * @param array $variables variables to be passed to template
     * @param array $options
     * @return string
     */
    public function render(string $template, array $variables = [], array $options = []) : string;
}