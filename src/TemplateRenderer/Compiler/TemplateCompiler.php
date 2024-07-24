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

use Lightning\TemplateRenderer\Exception\TemplateRendererException;

class TemplateCompiler implements TemplateCompilerInterface
{
    /**
     * Switch is purposley ignored
     */
    protected array $patterns = [
        '/(?<!\@)\{\{\s(.+?)\s\}\}/',
        '/@(if|elseif|foreach|for|while)(\s*\(.*\))/',
        '/@(endif|endforeach|endfor|endwhile)/',
        '/@else/'
    ];

    protected array $replacements = [
        '<?= $this->escape($1) ?>',
        '<?php $1$2: ?>',
        '<?php $1; ?>',
        '<?php else: ?>',
    ];

    public function __construct(private ?string $outputDirectory = null)
    {
        $this->outputDirectory = $outputDirectory ?? sys_get_temp_dir() . '/tr_compiled';

        if (! is_dir($this->outputDirectory)) {
            mkdir($this->outputDirectory, 0775, true);
        }
    }

    /**
     * Compiles a template and returns the path of the compiled template
     */
    public function compile(string $path): string
    {
        $compiled = $this->outputDirectory . '/' . md5($path) . '.php';

        if (file_exists($compiled) && filemtime($compiled) > filemtime($path)) {
            return $compiled;
        }

        if (! file_put_contents($compiled, preg_replace($this->patterns, $this->replacements, file_get_contents($path)))) {
            throw new TemplateRendererException('Error saving compiled template');
        }

        return $compiled;
    }
}
